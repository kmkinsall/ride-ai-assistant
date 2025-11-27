<?php
// Secure session configuration
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

// Load .env file
$envFile = __DIR__ . '/v2/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Get password hash from environment
$passwordHash = $_ENV['ACCESS_PASSWORD_HASH'] ?? null;
if (!$passwordHash) {
    die('Security configuration error: ACCESS_PASSWORD_HASH not set in .env');
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle login
$error = '';
$showLoading = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        if (password_verify($password, $passwordHash)) {
            // Regenerate session ID on successful login to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['authenticated'] = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // New token after login
            $showLoading = true;
        } else {
            $error = 'Incorrect password. Please try again.';
        }
    }
}

// Check if already authenticated (not from form submission)
if (!$showLoading && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: v2/index.php');
    exit;
}

// If showing loading screen, output that and exit
if ($showLoading):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Loading - RIDE AI Assistant</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        neutral: {
                            50: '#F4F3EE', 100: '#EFEEE8', 200: '#E5E4DE', 300: '#D5D4CE',
                            400: '#B1ADA1', 500: '#8A867A', 600: '#5C5850', 700: '#3D3A34',
                            800: '#2A2824', 900: '#1A1916',
                        },
                        dark: {
                            50: '#F4F3EE', 100: '#E5E4DE', 200: '#B1ADA1', 300: '#8A867A',
                            400: '#5C5850', 500: '#3D3A34', 600: '#2D2B28', 700: '#232220',
                            800: '#1A1918', 900: '#121110',
                        },
                        accent: {
                            50: '#FDF5F3', 100: '#FCE8E3', 200: '#F9D5CC', 300: '#F3B5A6',
                            400: '#E8907A', 500: '#DE7356', 600: '#C15F3C', 700: '#A14D32',
                            800: '#854130', 900: '#6F3A2D',
                        },
                    }
                }
            }
        }
    </script>
    <style>
        html { font-size: 17px; } /* Base font size - increase for larger text */
        * { font-family: 'Inter', system-ui, sans-serif; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
        }

        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        .animate-slide-up { animation: slideUp 0.6s ease-out forwards; }
        .animate-spin-slow { animation: spin 2s linear infinite; }
        .animate-pulse-slow { animation: pulse 2s ease-in-out infinite; }
        .animate-progress { animation: progress 2s ease-out forwards; }

        .delay-1 { animation-delay: 0.1s; opacity: 0; }
        .delay-2 { animation-delay: 0.3s; opacity: 0; }
        .delay-3 { animation-delay: 0.5s; opacity: 0; }
    </style>
</head>
<body class="bg-neutral-50 dark:bg-dark-800 min-h-screen flex items-center justify-center transition-colors duration-200">
    <div class="text-center px-6">
        <!-- Logo/Icon -->
        <div class="animate-fade-in mb-8">
            <div class="w-20 h-20 bg-accent-500 rounded-2xl flex items-center justify-center mx-auto shadow-lg shadow-accent-500/30">
                <svg class="w-10 h-10 text-white animate-pulse-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-2xl md:text-3xl font-bold text-neutral-800 dark:text-dark-50 mb-3 animate-slide-up delay-1">
            RIDE AI Guidance Assistant
        </h1>

        <!-- Subtitle -->
        <p class="text-neutral-500 dark:text-dark-200 mb-8 animate-slide-up delay-2">
            Preparing your workspace...
        </p>

        <!-- Progress Bar -->
        <div class="w-64 mx-auto animate-slide-up delay-3">
            <div class="h-1.5 bg-neutral-200 dark:bg-dark-600 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-accent-400 to-accent-600 rounded-full animate-progress"></div>
            </div>
            <p class="text-xs text-neutral-400 dark:text-dark-300 mt-3">Loading resources...</p>
        </div>
    </div>

    <script>
        // Apply saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }

        // Redirect after 2 seconds
        setTimeout(function() {
            window.location.href = 'v2/index.php';
        }, 2000);
    </script>
</body>
</html>
<?php
exit;
endif;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>RIDE AI Guidance Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        // Claude Light Mode - Pampas warm cream
                        neutral: {
                            50: '#F4F3EE',   // Main background (Pampas)
                            100: '#EFEEE8',  // Secondary background
                            200: '#E5E4DE',  // Borders, dividers
                            300: '#D5D4CE',  // Muted elements
                            400: '#B1ADA1',  // Placeholder text (Cloudy)
                            500: '#8A867A',  // Secondary text
                            600: '#5C5850',  // Primary text
                            700: '#3D3A34',  // Headers
                            800: '#2A2824',  // Strong emphasis
                            900: '#1A1916',  // Darkest
                        },
                        // Claude Dark Mode - Charcoal
                        dark: {
                            50: '#F4F3EE',   // Primary text (Pampas)
                            100: '#E5E4DE',  // Secondary text
                            200: '#B1ADA1',  // Muted text (Cloudy)
                            300: '#8A867A',  // Subtle text
                            400: '#5C5850',  // Borders
                            500: '#3D3A34',  // Elevated surfaces
                            600: '#2D2B28',  // Cards, panels
                            700: '#232220',  // Secondary background
                            800: '#1A1918',  // Main background
                            900: '#121110',  // Deepest background
                        },
                        // Claude accent - Peach/Terra Cotta
                        accent: {
                            50: '#FDF5F3',
                            100: '#FCE8E3',
                            200: '#F9D5CC',
                            300: '#F3B5A6',
                            400: '#E8907A',
                            500: '#DE7356',  // Primary accent (Claude Peach)
                            600: '#C15F3C',  // Hover state (Terra Cotta)
                            700: '#A14D32',
                            800: '#854130',
                            900: '#6F3A2D',
                        },
                        // Mode colors
                        blue: {
                            100: '#DBEAFE', 400: '#60A5FA', 600: '#2563EB',
                            900: '#1E3A5F',
                        },
                        amber: {
                            100: '#FEF3C7', 400: '#FBBF24', 600: '#D97706',
                            900: '#78350F',
                        },
                        emerald: {
                            400: '#34D399', 600: '#059669',
                        },
                    }
                }
            }
        }
    </script>
    <style>
        html { font-size: 17px; } /* Base font size - increase for larger text */
        * { font-family: 'Inter', system-ui, sans-serif; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
        .animate-delay-1 { animation-delay: 0.1s; opacity: 0; }
        .animate-delay-2 { animation-delay: 0.2s; opacity: 0; }
        .animate-delay-3 { animation-delay: 0.3s; opacity: 0; }
        .animate-delay-4 { animation-delay: 0.4s; opacity: 0; }
        /* Dark mode icon toggle */
        .dark .dark-icon { display: none; }
        .dark .light-icon { display: block !important; }
        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #EFEEE8; }
        ::-webkit-scrollbar-thumb { background: #D5D4CE; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #B1ADA1; }
        .dark ::-webkit-scrollbar-track { background: #232220; }
        .dark ::-webkit-scrollbar-thumb { background: #3D3A34; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #5C5850; }

        /* Mobile optimizations for iPhone portrait mode */
        @media (max-width: 767px) {
            html { font-size: 16px; }

            /* Safe area for iPhone notch and home indicator */
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
                padding-left: env(safe-area-inset-left);
                padding-right: env(safe-area-inset-right);
            }

            /* Larger touch targets (Apple minimum 44px) */
            button, a { min-height: 44px; }

            /* Prevent iOS zoom on input focus */
            #password { font-size: 16px !important; }

            /* Responsive header */
            header .max-w-6xl { padding-left: 1rem; padding-right: 1rem; }
            header h1 { font-size: 0.9375rem; }
            header p { font-size: 0.6875rem; }

            /* Responsive hero section */
            .py-16 { padding-top: 2.5rem; padding-bottom: 2.5rem; }
            .md\:py-24 { padding-top: 2.5rem; padding-bottom: 2.5rem; }

            /* Slideshow improvements for mobile */
            #prevSlide, #nextSlide {
                opacity: 1 !important;
                width: 2.5rem;
                height: 2.5rem;
            }

            /* Grid improvements */
            .grid.md\:grid-cols-3 { gap: 1rem; }
            .grid.md\:grid-cols-2 { gap: 1.5rem; }

            /* Footer adjustments */
            footer { text-align: center; }
            footer .flex { flex-direction: column; }
        }

        /* Extra small screens (iPhone SE, etc.) */
        @media (max-width: 375px) {
            html { font-size: 15px; }

            /* Compact header */
            header h1 { font-size: 0.875rem; }
            .w-10.h-10 { width: 2rem; height: 2rem; }
            header .w-5.h-5 { width: 1rem; height: 1rem; }

            /* Smaller hero text */
            .text-3xl { font-size: 1.5rem; }
            .text-lg { font-size: 0.9375rem; }
        }

        /* Prevent horizontal scroll */
        body { overflow-x: hidden; }

        /* Smooth scrolling for iOS */
        html { -webkit-overflow-scrolling: touch; scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-neutral-50 dark:bg-dark-800 min-h-screen transition-colors duration-200">
    <!-- Header -->
    <header class="bg-white dark:bg-dark-700 border-b border-neutral-200 dark:border-dark-500 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-accent-500 rounded-lg flex items-center justify-center">
                    <i data-lucide="book-open" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-neutral-800 dark:text-dark-50">RIDE AI Guidance Assistant</h1>
                    <p class="text-xs text-neutral-500 dark:text-dark-200">Rhode Island Department of Education</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button id="darkModeBtn" class="p-2 hover:bg-neutral-100 dark:hover:bg-dark-600 rounded-lg transition-colors" title="Toggle Dark Mode">
                    <i data-lucide="moon" class="w-5 h-5 text-neutral-600 dark:text-dark-100 dark-icon"></i>
                    <i data-lucide="sun" class="w-5 h-5 text-neutral-600 dark:text-dark-100 light-icon hidden"></i>
                </button>
                <a href="#login" class="px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium rounded-lg transition-colors">
                    Access Tool
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-gradient-to-b from-accent-50 to-neutral-50 dark:from-dark-700 dark:to-dark-800 py-16 md:py-24">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <div class="animate-fade-in-up">
                <span class="inline-block px-3 py-1 bg-accent-100 dark:bg-accent-900/30 text-accent-700 dark:text-accent-300 text-xs font-medium rounded-full mb-4">
                    August 2025 Framework
                </span>
            </div>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-neutral-800 dark:text-dark-50 mb-6 animate-fade-in-up animate-delay-1">
                Navigate AI in Education<br>with Confidence
            </h2>
            <p class="text-lg text-neutral-600 dark:text-dark-200 max-w-2xl mx-auto mb-8 animate-fade-in-up animate-delay-2">
                An intelligent assistant designed to help Rhode Island educators understand, implement,
                and communicate AI guidance in their schools and districts.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center animate-fade-in-up animate-delay-3">
                <a href="#about" class="px-6 py-3 bg-white dark:bg-dark-600 border border-neutral-300 dark:border-dark-400 text-neutral-700 dark:text-dark-100 font-medium rounded-lg hover:bg-neutral-50 dark:hover:bg-dark-500 transition-colors">
                    Learn More
                </a>
                <a href="#login" class="px-6 py-3 bg-accent-500 hover:bg-accent-600 text-white font-medium rounded-lg transition-colors">
                    Get Started
                </a>
            </div>
        </div>
    </section>

    <!-- Screenshot Slideshow Section -->
    <section class="py-12 bg-white dark:bg-dark-700">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-8">
                <h3 class="text-xl md:text-2xl font-bold text-neutral-800 dark:text-dark-50 mb-2">See It In Action</h3>
                <p class="text-neutral-500 dark:text-dark-300 text-sm">Preview the assistant's features</p>
            </div>
            <div class="relative max-w-4xl mx-auto">
                <!-- Slideshow Container -->
                <div id="slideshow" class="relative overflow-hidden rounded-2xl shadow-2xl border border-neutral-200 dark:border-dark-500">
                    <div id="slides" class="flex transition-transform duration-500 ease-in-out">
                        <img src="Ride1.png" alt="RIDE AI Assistant - Welcome Screen" class="w-full flex-shrink-0">
                        <img src="Ride2.png" alt="RIDE AI Assistant - Chat Interface" class="w-full flex-shrink-0">
                        <img src="Ride3.png" alt="RIDE AI Assistant - Learn Mode" class="w-full flex-shrink-0">
                        <img src="Ride4.png" alt="RIDE AI Assistant - Build Mode" class="w-full flex-shrink-0">
                    </div>
                </div>
                <!-- Navigation Arrows -->
                <button id="prevSlide" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 dark:bg-dark-800/80 hover:bg-white dark:hover:bg-dark-700 rounded-full shadow-lg flex items-center justify-center transition-all opacity-0 group-hover:opacity-100" aria-label="Previous slide">
                    <i data-lucide="chevron-left" class="w-5 h-5 text-neutral-700 dark:text-dark-100"></i>
                </button>
                <button id="nextSlide" class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 dark:bg-dark-800/80 hover:bg-white dark:hover:bg-dark-700 rounded-full shadow-lg flex items-center justify-center transition-all opacity-0 group-hover:opacity-100" aria-label="Next slide">
                    <i data-lucide="chevron-right" class="w-5 h-5 text-neutral-700 dark:text-dark-100"></i>
                </button>
                <!-- Dots Indicator -->
                <div class="flex justify-center gap-2 mt-4">
                    <button class="slide-dot w-2.5 h-2.5 rounded-full bg-accent-500 transition-all" data-slide="0"></button>
                    <button class="slide-dot w-2.5 h-2.5 rounded-full bg-neutral-300 dark:bg-dark-500 hover:bg-neutral-400 dark:hover:bg-dark-400 transition-all" data-slide="1"></button>
                    <button class="slide-dot w-2.5 h-2.5 rounded-full bg-neutral-300 dark:bg-dark-500 hover:bg-neutral-400 dark:hover:bg-dark-400 transition-all" data-slide="2"></button>
                    <button class="slide-dot w-2.5 h-2.5 rounded-full bg-neutral-300 dark:bg-dark-500 hover:bg-neutral-400 dark:hover:bg-dark-400 transition-all" data-slide="3"></button>
                </div>
            </div>
        </div>
    </section>

    <!-- What Is This Section -->
    <section id="about" class="py-16 bg-neutral-50 dark:bg-dark-800">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-12">
                <h3 class="text-2xl md:text-3xl font-bold text-neutral-800 dark:text-dark-50 mb-4">What is This Tool?</h3>
                <p class="text-neutral-600 dark:text-dark-200 max-w-2xl mx-auto">
                    The RIDE AI Guidance Assistant is an AI-powered tool that helps educators navigate
                    Rhode Island's official AI guidance framework for K-12 schools.
                </p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-neutral-50 dark:bg-dark-600 rounded-2xl p-6 border border-neutral-200 dark:border-dark-500">
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="message-circle-question" class="w-6 h-6 text-accent-600 dark:text-accent-400"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-neutral-800 dark:text-dark-50 mb-2">Ask Questions</h4>
                    <p class="text-sm text-neutral-600 dark:text-dark-200">
                        Get instant, accurate answers about RIDE's AI policies, grade-level recommendations,
                        data privacy requirements, and more.
                    </p>
                </div>
                <div class="bg-neutral-50 dark:bg-dark-600 rounded-2xl p-6 border border-neutral-200 dark:border-dark-500">
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="file-text" class="w-6 h-6 text-accent-600 dark:text-accent-400"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-neutral-800 dark:text-dark-50 mb-2">Build Policies</h4>
                    <p class="text-sm text-neutral-600 dark:text-dark-200">
                        Create AI acceptable use policies, parent communication letters, and professional
                        development plans tailored to your district.
                    </p>
                </div>
                <div class="bg-neutral-50 dark:bg-dark-600 rounded-2xl p-6 border border-neutral-200 dark:border-dark-500">
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="graduation-cap" class="w-6 h-6 text-accent-600 dark:text-accent-400"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-neutral-800 dark:text-dark-50 mb-2">Learn Best Practices</h4>
                    <p class="text-sm text-neutral-600 dark:text-dark-200">
                        Understand developmentally appropriate AI use, academic integrity considerations,
                        and equity concerns in AI adoption.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Use This Section -->
    <section class="py-16 bg-neutral-50 dark:bg-dark-800">
        <div class="max-w-5xl mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl md:text-3xl font-bold text-neutral-800 dark:text-dark-50 mb-6">Why Use This Tool?</h3>
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-accent-500 rounded-full flex items-center justify-center text-white text-sm font-bold">1</div>
                            <div>
                                <h5 class="font-semibold text-neutral-800 dark:text-dark-50">Save Time</h5>
                                <p class="text-sm text-neutral-600 dark:text-dark-200">Get answers instantly instead of searching through lengthy documents.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-accent-500 rounded-full flex items-center justify-center text-white text-sm font-bold">2</div>
                            <div>
                                <h5 class="font-semibold text-neutral-800 dark:text-dark-50">Stay Compliant</h5>
                                <p class="text-sm text-neutral-600 dark:text-dark-200">Ensure your AI practices align with FERPA, COPPA, and state requirements.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-accent-500 rounded-full flex items-center justify-center text-white text-sm font-bold">3</div>
                            <div>
                                <h5 class="font-semibold text-neutral-800 dark:text-dark-50">Personalized Guidance</h5>
                                <p class="text-sm text-neutral-600 dark:text-dark-200">Set your district profile to receive tailored recommendations.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-accent-500 rounded-full flex items-center justify-center text-white text-sm font-bold">4</div>
                            <div>
                                <h5 class="font-semibold text-neutral-800 dark:text-dark-50">Build Confidence</h5>
                                <p class="text-sm text-neutral-600 dark:text-dark-200">Lead AI conversations with parents, staff, and community members.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-dark-600 rounded-2xl border border-neutral-200 dark:border-dark-500 p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center">
                            <i data-lucide="users" class="w-5 h-5 text-accent-600 dark:text-accent-400"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-neutral-800 dark:text-dark-50">Who Is This For?</h4>
                        </div>
                    </div>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-sm text-neutral-600 dark:text-dark-200">
                            <i data-lucide="check-circle" class="w-5 h-5 text-accent-500 flex-shrink-0"></i>
                            <span>Superintendents & District Administrators</span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-neutral-600 dark:text-dark-200">
                            <i data-lucide="check-circle" class="w-5 h-5 text-accent-500 flex-shrink-0"></i>
                            <span>Principals & School Leaders</span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-neutral-600 dark:text-dark-200">
                            <i data-lucide="check-circle" class="w-5 h-5 text-accent-500 flex-shrink-0"></i>
                            <span>Curriculum Directors</span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-neutral-600 dark:text-dark-200">
                            <i data-lucide="check-circle" class="w-5 h-5 text-accent-500 flex-shrink-0"></i>
                            <span>Technology Coordinators</span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-neutral-600 dark:text-dark-200">
                            <i data-lucide="check-circle" class="w-5 h-5 text-accent-500 flex-shrink-0"></i>
                            <span>Teachers & Instructional Coaches</span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-neutral-600 dark:text-dark-200">
                            <i data-lucide="check-circle" class="w-5 h-5 text-accent-500 flex-shrink-0"></i>
                            <span>School Board Members</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- How to Use Section -->
    <section class="py-16 bg-white dark:bg-dark-700">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-12">
                <h3 class="text-2xl md:text-3xl font-bold text-neutral-800 dark:text-dark-50 mb-4">How to Use</h3>
                <p class="text-neutral-600 dark:text-dark-200 max-w-2xl mx-auto">
                    Getting started is simple. Follow these steps to make the most of the assistant.
                </p>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-accent-500 rounded-lg flex items-center justify-center">
                            <i data-lucide="log-in" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-neutral-800 dark:text-dark-50 mb-2">1. Log In</h4>
                        <p class="text-sm text-neutral-600 dark:text-dark-200">
                            Enter the access password below to enter the tool. Contact your administrator
                            if you don't have the password.
                        </p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-accent-500 rounded-lg flex items-center justify-center">
                            <i data-lucide="toggle-left" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-neutral-800 dark:text-dark-50 mb-2">2. Choose Your Mode</h4>
                        <p class="text-sm text-neutral-600 dark:text-dark-200">
                            <strong class="text-neutral-800 dark:text-dark-50">Learn Mode</strong> for exploring and understanding the guidance.
                            <strong class="text-neutral-800 dark:text-dark-50">Build Mode</strong> for creating policies and documents.
                        </p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-accent-500 rounded-lg flex items-center justify-center">
                            <i data-lucide="building-2" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-neutral-800 dark:text-dark-50 mb-2">3. Set Your District Profile</h4>
                        <p class="text-sm text-neutral-600 dark:text-dark-200">
                            Optional but recommended. Add your district name, role, and challenges to
                            receive personalized recommendations.
                        </p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-accent-500 rounded-lg flex items-center justify-center">
                            <i data-lucide="message-square" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-neutral-800 dark:text-dark-50 mb-2">4. Start Asking Questions</h4>
                        <p class="text-sm text-neutral-600 dark:text-dark-200">
                            Type your questions in the chat. Use the reference panel on the right to
                            browse the full RIDE guidance document.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- AI Technology Section -->
    <section class="py-16 bg-neutral-50 dark:bg-dark-800">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-12">
                <h3 class="text-2xl md:text-3xl font-bold text-neutral-800 dark:text-dark-50 mb-4">Powered by Advanced AI</h3>
                <p class="text-neutral-600 dark:text-dark-200 max-w-2xl mx-auto">
                    This assistant uses OpenAI's latest GPT-5.1 model with two specialized modes optimized for different tasks.
                </p>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Learn Mode Card -->
                <div class="bg-white dark:bg-dark-600 rounded-2xl border border-neutral-200 dark:border-dark-500 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <i data-lucide="graduation-cap" class="w-6 h-6 text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-neutral-800 dark:text-dark-50">Learn Mode</h4>
                            <p class="text-xs text-neutral-500 dark:text-dark-300">Explore & understand guidance</p>
                        </div>
                    </div>
                    <p class="text-sm text-neutral-600 dark:text-dark-200 mb-4">
                        Conversational explanations with narrative flow. Perfect for understanding policies, exploring topics, and learning best practices.
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between py-1 border-b border-neutral-100 dark:border-dark-500">
                            <span class="text-neutral-500 dark:text-dark-300">Model</span>
                            <span class="font-mono text-neutral-800 dark:text-dark-100">GPT-5.1</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100 dark:border-dark-500">
                            <span class="text-neutral-500 dark:text-dark-300">Reasoning</span>
                            <span class="font-mono text-neutral-800 dark:text-dark-100">Standard</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100 dark:border-dark-500">
                            <span class="text-neutral-500 dark:text-dark-300">Temperature</span>
                            <span class="font-mono text-neutral-800 dark:text-dark-100">0.7</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-neutral-500 dark:text-dark-300">Response Style</span>
                            <span class="font-mono text-neutral-800 dark:text-dark-100">Concise</span>
                        </div>
                    </div>
                </div>
                <!-- Build Mode Card -->
                <div class="bg-white dark:bg-dark-600 rounded-2xl border border-neutral-200 dark:border-dark-500 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                            <i data-lucide="hammer" class="w-6 h-6 text-amber-600 dark:text-amber-400"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-neutral-800 dark:text-dark-50">Build Mode</h4>
                            <p class="text-xs text-neutral-500 dark:text-dark-300">Plan & implement policies</p>
                        </div>
                    </div>
                    <p class="text-sm text-neutral-600 dark:text-dark-200 mb-4">
                        Strategic assistance for creating policies, drafting documents, and developing implementation plans for your district.
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between py-1 border-b border-neutral-100 dark:border-dark-500">
                            <span class="text-neutral-500 dark:text-dark-300">Model</span>
                            <span class="font-mono text-neutral-800 dark:text-dark-100">GPT-5.1</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100 dark:border-dark-500">
                            <span class="text-neutral-500 dark:text-dark-300">Reasoning</span>
                            <span class="font-mono text-neutral-800 dark:text-dark-100">Enhanced</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100 dark:border-dark-500">
                            <span class="text-neutral-500 dark:text-dark-300">Approach</span>
                            <span class="font-mono text-neutral-800 dark:text-dark-100">Thorough</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-neutral-500 dark:text-dark-300">Response Style</span>
                            <span class="font-mono text-neutral-800 dark:text-dark-100">Detailed</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-100 dark:bg-dark-600 rounded-full">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                    <span class="text-sm text-neutral-600 dark:text-dark-200">Grounded in official RIDE AI Guidance 2025 documentation</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Section -->
    <section id="login" class="py-16 bg-gradient-to-b from-neutral-50 to-accent-50 dark:from-dark-800 dark:to-dark-700">
        <div class="max-w-md mx-auto px-6">
            <div class="bg-white dark:bg-dark-600 rounded-2xl border border-neutral-200 dark:border-dark-500 shadow-lg p-8">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-accent-100 dark:bg-accent-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="lock" class="w-8 h-8 text-accent-600 dark:text-accent-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-neutral-800 dark:text-dark-50 mb-2">Access the Tool</h3>
                    <p class="text-sm text-neutral-500 dark:text-dark-200">Enter the password to continue</p>
                </div>

                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 dark:text-red-400 flex-shrink-0"></i>
                    <span class="text-sm text-red-700 dark:text-red-300"><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-neutral-700 dark:text-dark-100 mb-2">Password</label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                class="w-full px-4 py-3 bg-neutral-50 dark:bg-dark-700 border border-neutral-200 dark:border-dark-400 rounded-xl text-neutral-800 dark:text-dark-50 placeholder-neutral-400 dark:placeholder-dark-300 focus:outline-none focus:ring-2 focus:ring-accent-200 dark:focus:ring-accent-700 focus:border-accent-400 dark:focus:border-accent-500 transition-all"
                                placeholder="Enter access password"
                            >
                            <button type="button" id="togglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-neutral-400 dark:text-dark-300 hover:text-neutral-600 dark:hover:text-dark-100 transition-colors">
                                <i data-lucide="eye" class="w-5 h-5" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button
                        type="submit"
                        class="w-full py-3 bg-accent-500 hover:bg-accent-600 text-white font-medium rounded-xl transition-colors flex items-center justify-center gap-2"
                    >
                        <span>Enter</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </form>

                <!-- Terms Notice -->
                <div class="mt-6 pt-5 border-t border-neutral-200 dark:border-dark-500">
                    <div class="flex items-start gap-3">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-neutral-400 dark:text-dark-400 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <h4 class="font-medium text-neutral-600 dark:text-dark-200 mb-1 text-xs">Terms of Use</h4>
                            <p class="text-xs text-neutral-500 dark:text-dark-400 leading-relaxed">This AI assistant is provided for <strong>informational purposes only</strong>. Responses are generated by artificial intelligence and may contain errors or inaccuracies. Users are responsible for verifying all information before making decisions or implementing policies.</p>
                            <p class="text-xs text-neutral-500 dark:text-dark-400 leading-relaxed mt-2">This tool does not collect, store, or process student personally identifiable information (PII). Do not input sensitive student data.</p>
                            <p class="text-xs text-accent-600 dark:text-accent-400 mt-3 font-medium">By logging in, you agree to these terms.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-neutral-800 dark:bg-dark-900 text-neutral-300 dark:text-dark-200 py-8">
        <div class="max-w-5xl mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-accent-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="book-open" class="w-4 h-4 text-white"></i>
                    </div>
                    <span class="text-sm font-medium text-white dark:text-dark-50">RIDE AI Guidance Assistant</span>
                </div>
                <p class="text-xs text-neutral-400 dark:text-dark-300 text-center md:text-right">
                    Based on the Rhode Island Department of Education AI Guidance Framework (August 2025).<br>
                    This is an educational tool and not an official RIDE product.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Initialize dark mode from localStorage
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }

        // Dark mode toggle
        const darkModeBtn = document.getElementById('darkModeBtn');
        darkModeBtn.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('darkMode', isDark);
        });

        // Ensure page starts at top on load
        window.scrollTo(0, 0);
        if (window.location.hash) {
            history.replaceState(null, null, window.location.pathname);
        }

        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.setAttribute('data-lucide', type === 'password' ? 'eye' : 'eye-off');
            lucide.createIcons();
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Slideshow functionality
        const slides = document.getElementById('slides');
        const dots = document.querySelectorAll('.slide-dot');
        const prevBtn = document.getElementById('prevSlide');
        const nextBtn = document.getElementById('nextSlide');
        const slideshow = document.getElementById('slideshow');
        let currentSlide = 0;
        const totalSlides = 4;
        let autoSlideInterval;

        function goToSlide(index) {
            currentSlide = (index + totalSlides) % totalSlides;
            slides.style.transform = `translateX(-${currentSlide * 100}%)`;
            dots.forEach((dot, i) => {
                if (i === currentSlide) {
                    dot.classList.remove('bg-neutral-300', 'dark:bg-dark-500');
                    dot.classList.add('bg-accent-500');
                } else {
                    dot.classList.remove('bg-accent-500');
                    dot.classList.add('bg-neutral-300', 'dark:bg-dark-500');
                }
            });
        }

        function nextSlide() {
            goToSlide(currentSlide + 1);
        }

        function prevSlide() {
            goToSlide(currentSlide - 1);
        }

        // Button events
        nextBtn.addEventListener('click', () => {
            nextSlide();
            resetAutoSlide();
        });

        prevBtn.addEventListener('click', () => {
            prevSlide();
            resetAutoSlide();
        });

        // Dot events
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                goToSlide(index);
                resetAutoSlide();
            });
        });

        // Show/hide arrows on hover
        slideshow.parentElement.addEventListener('mouseenter', () => {
            prevBtn.classList.remove('opacity-0');
            prevBtn.classList.add('opacity-100');
            nextBtn.classList.remove('opacity-0');
            nextBtn.classList.add('opacity-100');
        });

        slideshow.parentElement.addEventListener('mouseleave', () => {
            prevBtn.classList.remove('opacity-100');
            prevBtn.classList.add('opacity-0');
            nextBtn.classList.remove('opacity-100');
            nextBtn.classList.add('opacity-0');
        });

        // Auto-advance slides
        function startAutoSlide() {
            autoSlideInterval = setInterval(nextSlide, 5000);
        }

        function resetAutoSlide() {
            clearInterval(autoSlideInterval);
            startAutoSlide();
        }

        startAutoSlide();
    </script>
</body>
</html>
