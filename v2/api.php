<?php
/**
 * RIDE AI Guidance Assistant v2 - API Endpoint
 * Uses OpenAI Responses API with streaming support
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Secure session configuration
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

// Check authentication (except for CORS preflight)
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized. Please log in.']);
        exit;
    }
}

/**
 * Rate limiting function
 * Limits requests per session within a time window
 */
function checkRateLimit(): bool {
    // Get rate limit settings from environment or use defaults
    $maxRequests = isset($_ENV['RATE_LIMIT_REQUESTS']) ? (int)$_ENV['RATE_LIMIT_REQUESTS'] : 20;
    $windowSeconds = isset($_ENV['RATE_LIMIT_WINDOW']) ? (int)$_ENV['RATE_LIMIT_WINDOW'] : 60;

    $now = time();

    // Initialize rate limit tracking in session
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [
            'requests' => [],
            'blocked_until' => 0
        ];
    }

    // Check if currently blocked
    if ($_SESSION['rate_limit']['blocked_until'] > $now) {
        return false;
    }

    // Clean old requests outside the window
    $_SESSION['rate_limit']['requests'] = array_filter(
        $_SESSION['rate_limit']['requests'],
        function($timestamp) use ($now, $windowSeconds) {
            return $timestamp > ($now - $windowSeconds);
        }
    );

    // Check if limit exceeded
    if (count($_SESSION['rate_limit']['requests']) >= $maxRequests) {
        // Block for the remainder of the window
        $_SESSION['rate_limit']['blocked_until'] = $now + $windowSeconds;
        return false;
    }

    // Record this request
    $_SESSION['rate_limit']['requests'][] = $now;

    return true;
}

// Apply rate limiting (except for CORS preflight)
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    if (!checkRateLimit()) {
        header('Content-Type: application/json');
        http_response_code(429);
        $retryAfter = $_SESSION['rate_limit']['blocked_until'] - time();
        header('Retry-After: ' . $retryAfter);
        echo json_encode([
            'error' => 'Too many requests. Please wait ' . $retryAfter . ' seconds before trying again.'
        ]);
        exit;
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require_once 'config.php';
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Config error: ' . $e->getMessage()]);
    exit;
}

/**
 * Load the knowledge base document
 */
function loadKnowledgeBase(): string {
    if (!defined('KNOWLEDGE_BASE_PATH') || !file_exists(KNOWLEDGE_BASE_PATH)) {
        return '';
    }
    return file_get_contents(KNOWLEDGE_BASE_PATH);
}

/**
 * Stream response from OpenAI Responses API using Server-Sent Events
 * GPT-5.1: temperature/top_p only supported when reasoning effort is 'none'
 */
function streamResponsesAPI(string $instructions, array $input, string $reasoningEffort, string $verbosity, ?float $temperature = null, ?float $topP = null): void {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');

    if (ob_get_level()) ob_end_clean();

    $data = [
        'model' => OPENAI_MODEL,
        'instructions' => $instructions,
        'input' => $input,
        'reasoning' => ['effort' => $reasoningEffort],
        'text' => ['verbosity' => $verbosity],
        'stream' => true
    ];

    // GPT-5.1: temperature/top_p ONLY work with reasoning effort 'none'
    if ($reasoningEffort === 'none' && $temperature !== null) {
        $data['temperature'] = $temperature;
    }
    if ($reasoningEffort === 'none' && $topP !== null) {
        $data['top_p'] = $topP;
    }

    $ch = curl_init(OPENAI_API_URL);

    if ($ch === false) {
        echo "data: " . json_encode(['error' => 'Failed to initialize cURL']) . "\n\n";
        echo "data: [DONE]\n\n";
        flush();
        return;
    }

    $currentEvent = '';

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_WRITEFUNCTION => function($ch, $chunk) use (&$currentEvent) {
            $lines = explode("\n", $chunk);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Track event type
                if (strpos($line, 'event: ') === 0) {
                    $currentEvent = trim(substr($line, 7));
                    continue;
                }

                // Handle data lines
                if (strpos($line, 'data: ') === 0) {
                    $jsonStr = substr($line, 6);

                    // Check for done signal
                    if ($jsonStr === '[DONE]') {
                        echo "data: [DONE]\n\n";
                        flush();
                        continue;
                    }

                    $decoded = json_decode($jsonStr, true);

                    // Handle text delta events (main content)
                    if ($currentEvent === 'response.output_text.delta' && isset($decoded['delta'])) {
                        echo "data: " . json_encode(['content' => $decoded['delta']]) . "\n\n";
                        flush();
                    }

                    // Handle completed event
                    if ($currentEvent === 'response.completed') {
                        echo "data: [DONE]\n\n";
                        flush();
                    }

                    // Handle errors
                    if (isset($decoded['error'])) {
                        $errorMsg = $decoded['error']['message'] ?? 'Unknown error';
                        echo "data: " . json_encode(['error' => $errorMsg]) . "\n\n";
                        flush();
                    }
                }
            }
            return strlen($chunk);
        }
    ]);

    $result = curl_exec($ch);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno) {
        echo "data: " . json_encode(['error' => 'cURL error: ' . $error]) . "\n\n";
    }

    echo "data: [DONE]\n\n";
    flush();
}

/**
 * Non-streaming request to OpenAI Responses API
 * GPT-5.1: temperature/top_p only supported when reasoning effort is 'none'
 */
function callResponsesAPI(string $instructions, array $input, string $reasoningEffort, string $verbosity, ?float $temperature = null, ?float $topP = null): array {
    $data = [
        'model' => OPENAI_MODEL,
        'instructions' => $instructions,
        'input' => $input,
        'reasoning' => ['effort' => $reasoningEffort],
        'text' => ['verbosity' => $verbosity]
    ];

    // GPT-5.1: temperature/top_p ONLY work with reasoning effort 'none'
    if ($reasoningEffort === 'none' && $temperature !== null) {
        $data['temperature'] = $temperature;
    }
    if ($reasoningEffort === 'none' && $topP !== null) {
        $data['top_p'] = $topP;
    }

    $ch = curl_init(OPENAI_API_URL);

    if ($ch === false) {
        return ['error' => 'Failed to initialize cURL'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno) {
        return ['error' => 'cURL error (' . $errno . '): ' . $error];
    }

    if (empty($response)) {
        return ['error' => 'Empty response from OpenAI API'];
    }

    $decoded = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Invalid JSON response: ' . json_last_error_msg()];
    }

    if ($httpCode !== 200) {
        $errorMessage = $decoded['error']['message'] ?? 'Unknown API error';
        return ['error' => 'API error (' . $httpCode . '): ' . $errorMessage];
    }

    return $decoded;
}

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    exit;
}

// Main request handling
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input: ' . json_last_error_msg()]);
        exit;
    }

    if (!$inputData || !isset($inputData['message'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Message is required']);
        exit;
    }

    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY) || OPENAI_API_KEY === 'your-openai-api-key-here') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'OpenAI API key not configured']);
        exit;
    }

    $userMessage = trim($inputData['message']);
    $conversationHistory = $inputData['history'] ?? [];
    $useStreaming = ENABLE_STREAMING && (isset($inputData['stream']) && $inputData['stream'] === true);
    $mode = $inputData['mode'] ?? 'learn';
    $districtContext = $inputData['districtContext'] ?? null;

    $knowledgeBase = loadKnowledgeBase();

    if (empty($knowledgeBase)) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Knowledge base not found']);
        exit;
    }

    // Build instructions (system prompt + mode prompt + knowledge base)
    $instructions = SYSTEM_PROMPT;

    // GPT-5.1 mode-specific settings
    // temperature/top_p ONLY work with reasoning effort 'none' (Learn mode)
    if ($mode === 'build') {
        $instructions .= BUILD_MODE_PROMPT;
        $reasoningEffort = BUILD_MODE_REASONING;
        $verbosity = BUILD_MODE_VERBOSITY;
        $temperature = null;  // Not supported with medium reasoning
        $topP = null;
    } else {
        $instructions .= LEARN_MODE_PROMPT;
        $reasoningEffort = LEARN_MODE_REASONING;
        $verbosity = LEARN_MODE_VERBOSITY;
        $temperature = LEARN_MODE_TEMPERATURE;  // Works with 'none' reasoning
        $topP = LEARN_MODE_TOP_P;
    }

    // Add district context if provided
    if (!empty($districtContext)) {
        $instructions .= "\n\n## DISTRICT PROFILE (User's Context)\n";
        $instructions .= "The user has provided the following information about their district. USE THIS ACTIVELY in your responses:\n\n";
        $instructions .= $districtContext;
        $instructions .= "\n\n**How to use this context:**\n";
        $instructions .= "- Reference their district name when appropriate\n";
        $instructions .= "- Tailor recommendations to their district size and resources\n";
        $instructions .= "- Acknowledge their current AI policy status and build from there\n";
        $instructions .= "- Address their specific challenges directly\n";
        $instructions .= "- Adjust complexity of recommendations based on their role\n";
        $instructions .= "- Make your guidance actionable for their specific situation\n";
    }

    // Append knowledge base
    $instructions .= "\n\n## RIDE AI GUIDANCE 2025 DOCUMENT\n\n" . $knowledgeBase;

    // Build input array for Responses API
    $input = [];

    // Add conversation history
    foreach ($conversationHistory as $msg) {
        if (isset($msg['role']) && isset($msg['content'])) {
            $input[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }
    }

    // Add current user message
    $input[] = [
        'role' => 'user',
        'content' => $userMessage
    ];

    // Use streaming or regular response
    if ($useStreaming) {
        streamResponsesAPI($instructions, $input, $reasoningEffort, $verbosity, $temperature, $topP);
    } else {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $response = callResponsesAPI($instructions, $input, $reasoningEffort, $verbosity, $temperature, $topP);

        if (isset($response['error'])) {
            http_response_code(500);
            echo json_encode($response);
            exit;
        }

        // Extract text from Responses API output
        $assistantMessage = '';
        if (isset($response['output_text'])) {
            $assistantMessage = $response['output_text'];
        } elseif (isset($response['output']) && is_array($response['output'])) {
            foreach ($response['output'] as $item) {
                if ($item['type'] === 'message' && isset($item['content'])) {
                    foreach ($item['content'] as $content) {
                        if ($content['type'] === 'output_text') {
                            $assistantMessage .= $content['text'];
                        }
                    }
                }
            }
        }

        if (empty($assistantMessage)) {
            http_response_code(500);
            echo json_encode(['error' => 'No response content from OpenAI']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => $assistantMessage
        ]);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} catch (Error $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Fatal error: ' . $e->getMessage()]);
}
