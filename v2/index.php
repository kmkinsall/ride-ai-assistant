<?php
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Check authentication
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>RIDE AI Guidance Assistant v2</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23171717'/><text x='50' y='68' font-size='50' text-anchor='middle' fill='white' font-family='system-ui'>AI</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
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
                            950: '#0A0909',  // True dark
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
                        // Keep functional colors for modes
                        blue: {
                            50: '#F4F6F8', 100: '#E2E8ED', 200: '#C5D1DB', 300: '#9FB3C4',
                            400: '#7393A9', 500: '#527489', 600: '#405B6D', 700: '#324857',
                            800: '#263742', 900: '#1A262E',
                        },
                        amber: {
                            50: '#FBF8F4', 100: '#F5EDE2', 200: '#EAD9C5', 300: '#D9BFA0',
                            400: '#C4A07A', 500: '#A8835C', 600: '#8B6B48', 700: '#6F553A',
                            800: '#54402D', 900: '#3A2C20',
                        },
                        emerald: {
                            50: '#F4F8F6', 100: '#E2EDE7', 200: '#C0D9CC', 300: '#96C0AC',
                            400: '#6BA38A', 500: '#4D856C', 600: '#3D6A56', 700: '#305344',
                            800: '#243F34', 900: '#192B24',
                        },
                        red: {
                            50: '#FDF6F5', 100: '#F9E8E6', 200: '#F2CCC8', 300: '#E5A8A2',
                            400: '#D27E76', 500: '#B85C54', 600: '#964A43', 700: '#753A35',
                            800: '#562C28', 900: '#3A1E1B',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        html { font-size: 17px; } /* Base font size - increase for larger text */
        * { font-family: 'Inter', system-ui, sans-serif; }
        /* Scrollbar - Light mode (Claude Pampas) */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #EFEEE8; }
        ::-webkit-scrollbar-thumb { background: #D5D4CE; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #B1ADA1; }
        /* Scrollbar - Dark mode (Claude Charcoal) */
        .dark ::-webkit-scrollbar-track { background: #232220; }
        .dark ::-webkit-scrollbar-thumb { background: #3D3A34; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #5C5850; }
        /* Theme toggle icons */
        .dark .dark-icon { display: none; }
        .dark .light-icon { display: block !important; }
        /* Markdown content - Headers (Claude theme) */
        .markdown-content h1, .markdown-content h2, .markdown-content h3 { font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; color: #3D3A34; }
        .dark .markdown-content h1, .dark .markdown-content h2, .dark .markdown-content h3 { color: #F4F3EE; }
        .markdown-content h1 { font-size: 1.25rem; }
        .markdown-content h2 { font-size: 1.125rem; }
        .markdown-content h3 { font-size: 1rem; }
        /* Markdown content - Text */
        .markdown-content p { margin-bottom: 0.75rem; line-height: 1.75; color: #5C5850; }
        .dark .markdown-content p { color: #E5E4DE; }
        .markdown-content ul, .markdown-content ol { margin-left: 1.5rem; margin-bottom: 0.75rem; }
        .markdown-content li { margin-bottom: 0.25rem; line-height: 1.7; color: #5C5850; }
        .dark .markdown-content li { color: #E5E4DE; }
        .markdown-content ul { list-style-type: disc; }
        .markdown-content ol { list-style-type: decimal; }
        /* Markdown content - Code */
        .markdown-content code { background: #E5E4DE; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.875rem; font-family: 'Monaco', 'Menlo', monospace; color: #5C5850; }
        .dark .markdown-content code { background: #3D3A34; color: #E5E4DE; }
        .markdown-content pre { background: #2A2824; color: #F4F3EE; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin-bottom: 0.75rem; }
        .dark .markdown-content pre { background: #121110; }
        .markdown-content pre code { background: transparent; padding: 0; color: inherit; }
        /* Markdown content - Blockquote (Claude accent) */
        .markdown-content blockquote { border-left: 4px solid #DE7356; padding-left: 1rem; margin: 1rem 0; background: #EFEEE8; padding: 0.75rem 1rem; border-radius: 0 0.5rem 0.5rem 0; color: #5C5850; font-style: normal; }
        .dark .markdown-content blockquote { border-left-color: #C15F3C; background: #2D2B28; color: #E5E4DE; }
        /* Markdown content - Strong/Links */
        .markdown-content strong { font-weight: 600; color: #3D3A34; }
        .dark .markdown-content strong { color: #F4F3EE; }
        .markdown-content a { color: #DE7356; text-decoration: underline; }
        .markdown-content a:hover { color: #C15F3C; }
        .dark .markdown-content a { color: #E8907A; }
        .dark .markdown-content a:hover { color: #F3B5A6; }
        /* Markdown content - HR/Table */
        .markdown-content hr { border: none; border-top: 1px solid #E5E4DE; margin: 1rem 0; }
        .dark .markdown-content hr { border-top-color: #3D3A34; }
        .markdown-content table { width: 100%; border-collapse: collapse; margin-bottom: 0.75rem; }
        .markdown-content th, .markdown-content td { border: 1px solid #E5E4DE; padding: 0.5rem; text-align: left; }
        .dark .markdown-content th, .dark .markdown-content td { border-color: #3D3A34; }
        .markdown-content th { background: #EFEEE8; font-weight: 600; color: #3D3A34; }
        .dark .markdown-content th { background: #2D2B28; color: #F4F3EE; }
        .dark .markdown-content td { color: #E5E4DE; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .message-animate { animation: fadeIn 0.3s ease-out; }
        @keyframes pulse { 0%, 80%, 100% { opacity: 0.3; } 40% { opacity: 1; } }
        .loading-dot { animation: pulse 1.4s ease-in-out infinite; }
        .loading-dot:nth-child(2) { animation-delay: 0.2s; }
        .loading-dot:nth-child(3) { animation-delay: 0.4s; }

        /* Typing indicator animation */
        @keyframes typingBounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-4px); }
        }
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            animation: typingBounce 1.4s ease-in-out infinite;
        }
        .typing-dot:nth-child(1) { animation-delay: 0s; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        /* Copy button styles */
        .message-actions { opacity: 0; transition: opacity 0.2s ease; }
        .message-wrapper:hover .message-actions { opacity: 1; }
        .copy-btn:active { transform: scale(0.95); }
        @media (max-width: 767px) { .message-actions { opacity: 1; } }
        .sidebar-panel { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); transform-origin: right center; }
        .sidebar-panel.collapsed { width: 0 !important; opacity: 0; transform: translateX(100%); overflow: hidden; border-left: none; padding: 0; }
        .sidebar-panel.collapsed > * { opacity: 0; visibility: hidden; }

        /* Mobile / iPhone Portrait Optimizations */
        @media (max-width: 767px) {
            html { font-size: 16px; }

            /* Safe area for iPhone notch and home indicator */
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
                padding-left: env(safe-area-inset-left);
                padding-right: env(safe-area-inset-right);
            }

            /* Hide reference panel completely on mobile */
            .sidebar-panel { display: none !important; }

            /* Larger touch targets - exclude header icons */
            main button, main a, form button, form a { min-height: 44px; }
            header button, header a { min-height: auto; min-width: 44px; padding: 8px; }

            /* Fix chat input area */
            #messageInput { font-size: 16px !important; } /* Prevents iOS zoom on focus */

            /* Improve message readability */
            .markdown-content p { font-size: 0.9375rem; line-height: 1.6; }
            .markdown-content li { font-size: 0.9375rem; }

            /* Full width messages on mobile */
            .max-w-\[85\%\] { max-width: 95% !important; }
            .max-w-\[80\%\] { max-width: 90% !important; }
        }

        /* Extra small screens (iPhone SE, etc.) */
        @media (max-width: 375px) {
            html { font-size: 15px; }
            .markdown-content p { font-size: 0.875rem; }
        }

        /* Prevent horizontal scroll */
        body { overflow-x: hidden; }

        /* Smooth scrolling for iOS */
        #chatMessages { -webkit-overflow-scrolling: touch; }
        /* Knowledge base panel (Claude theme) */
        .kb-content h1 { font-size: 1.125rem; font-weight: 700; color: #3D3A34; margin-top: 1.5rem; margin-bottom: 0.5rem; }
        .kb-content h2 { font-size: 1rem; font-weight: 600; color: #5C5850; margin-top: 1.25rem; margin-bottom: 0.375rem; }
        .kb-content h3 { font-size: 0.875rem; font-weight: 600; color: #8A867A; margin-top: 1rem; margin-bottom: 0.25rem; }
        .kb-content p { font-size: 0.8125rem; color: #5C5850; margin-bottom: 0.5rem; line-height: 1.6; }
        .kb-content ul, .kb-content ol { font-size: 0.8125rem; color: #5C5850; margin-left: 1.5rem; margin-bottom: 0.5rem; }
        .kb-content li { margin-bottom: 0.125rem; line-height: 1.5; }
        .kb-content table { font-size: 0.75rem; width: 100%; border-collapse: collapse; margin: 0.75rem 0; }
        .kb-content th, .kb-content td { padding: 0.5rem; border: 1px solid #E5E4DE; text-align: left; }
        .kb-content th { background: #EFEEE8; font-weight: 600; color: #3D3A34; }
        .kb-content blockquote { font-size: 0.8125rem; background: #EFEEE8; border-left: 3px solid #DE7356; padding: 0.5rem 0.75rem; margin: 0.5rem 0; color: #5C5850; }
        .kb-content hr { margin: 0.75rem 0; border-color: #E5E4DE; }
        .dark .kb-content h1 { color: #F4F3EE; }
        .dark .kb-content h2 { color: #E5E4DE; }
        .dark .kb-content h3 { color: #E5E4DE; }
        .dark .kb-content p { color: #B1ADA1; }
        .dark .kb-content ul, .dark .kb-content ol { color: #B1ADA1; }
        .dark .kb-content th, .dark .kb-content td { border-color: #3D3A34; }
        .dark .kb-content th { background: #2D2B28; color: #F4F3EE; }
        .dark .kb-content td { color: #E5E4DE; }
        .dark .kb-content blockquote { background: #2D2B28; border-left-color: #C15F3C; color: #E5E4DE; }
        .dark .kb-content hr { border-color: #3D3A34; }
        .dark .kb-content li { color: #B1ADA1; }
    </style>
</head>
<body class="bg-neutral-50 dark:bg-dark-900 text-neutral-800 dark:text-dark-50 min-h-screen transition-colors duration-200">
    <div class="flex flex-col h-screen">
        <!-- Header -->
        <header class="bg-neutral-50 dark:bg-dark-800 border-b border-neutral-200 dark:border-dark-500 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-accent-500 dark:bg-accent-500 rounded-lg flex items-center justify-center">
                    <i data-lucide="book-open" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-neutral-900 dark:text-dark-50">RIDE AI Guidance Assistant</h1>
                    <p class="text-xs text-neutral-500 dark:text-dark-200">Rhode Island Department of Education - August 2025 Framework</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button id="districtProfileBtn" class="flex items-center gap-1.5 px-3 py-2 hover:bg-neutral-100 dark:hover:bg-dark-700 rounded-lg transition-colors" title="District Profile">
                    <i data-lucide="building-2" class="w-5 h-5 text-neutral-600 dark:text-dark-100"></i>
                    <span id="districtProfileLabel" class="text-xs font-medium text-neutral-600 dark:text-dark-100 hidden sm:inline">District Profile</span>
                    <span id="districtProfileBadge" class="hidden w-2 h-2 bg-green-500 rounded-full"></span>
                </button>
                <button id="clearChatBtn" class="p-2 hover:bg-neutral-100 dark:hover:bg-dark-700 rounded-lg transition-colors" title="Clear Chat">
                    <i data-lucide="trash-2" class="w-5 h-5 text-neutral-600 dark:text-dark-100"></i>
                </button>
                <button id="darkModeBtn" class="p-2 hover:bg-neutral-100 dark:hover:bg-dark-700 rounded-lg transition-colors" title="Toggle Dark Mode">
                    <i data-lucide="moon" class="w-5 h-5 text-neutral-600 dark:text-dark-100 dark-icon"></i>
                    <i data-lucide="sun" class="w-5 h-5 text-neutral-600 dark:text-dark-100 light-icon hidden"></i>
                </button>
                <button id="togglePanelBtn" class="p-2 hover:bg-neutral-100 dark:hover:bg-dark-700 rounded-lg transition-colors" title="Toggle Reference Panel">
                    <i data-lucide="panel-right" class="w-5 h-5 text-neutral-600 dark:text-dark-100"></i>
                </button>
                <button id="settingsBtn" class="p-2 hover:bg-neutral-100 dark:hover:bg-dark-700 rounded-lg transition-colors" title="AI Settings & Transparency">
                    <i data-lucide="settings" class="w-5 h-5 text-neutral-600 dark:text-dark-100"></i>
                </button>
                <div class="w-px h-6 bg-neutral-200 dark:bg-dark-500 mx-1"></div>
                <a href="logout.php" class="flex items-center gap-1.5 px-3 py-2 hover:bg-red-50 dark:hover:bg-red-900/20 text-neutral-600 dark:text-dark-100 hover:text-red-600 dark:hover:text-red-400 rounded-lg transition-colors" title="Log Out">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span class="text-xs font-medium hidden sm:inline">Log Out</span>
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex overflow-hidden">
            <!-- Chat Panel -->
            <div class="flex-1 flex flex-col bg-neutral-50 dark:bg-[#222221]">
                <!-- Chat Messages -->
                <div id="chatMessages" class="flex-1 overflow-y-auto p-6">
                    <div id="chatContent" class="max-w-[1100px] mx-auto">
                    <!-- Welcome Screen -->
                    <div id="welcomeScreen" class="max-w-2xl mx-auto py-8">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-semibold text-neutral-900 dark:text-dark-50 mb-2">Welcome to RIDE AI Guidance Assistant</h2>
                            <p class="text-neutral-500 dark:text-dark-200 max-w-md mx-auto">
                                I'm here to help you understand and implement the Rhode Island Department of Education's
                                AI Guidance Framework (August 2025).
                            </p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-8">
                            <button onclick="askQuestion('What are the core goals of the RIDE AI Guidance?')" class="text-left p-4 border border-neutral-200 dark:border-dark-600 rounded-xl hover:border-neutral-400 dark:hover:border-dark-500 hover:bg-neutral-50 dark:hover:bg-dark-800 transition-all group">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-neutral-100 dark:bg-dark-700 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-neutral-200 dark:group-hover:bg-dark-600 transition-colors">
                                        <i data-lucide="target" class="w-4 h-4 text-neutral-600 dark:text-dark-200"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-neutral-900 dark:text-dark-50 text-sm">Core Goals</h3>
                                        <p class="text-xs text-neutral-500 dark:text-dark-200 mt-0.5">What are the main objectives of the RIDE AI Guidance?</p>
                                    </div>
                                </div>
                            </button>
                            <button onclick="askQuestion('What does RIDE say about academic integrity and AI?')" class="text-left p-4 border border-neutral-200 dark:border-dark-600 rounded-xl hover:border-neutral-400 dark:hover:border-dark-500 hover:bg-neutral-50 dark:hover:bg-dark-800 transition-all group">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-neutral-100 dark:bg-dark-700 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-neutral-200 dark:group-hover:bg-dark-600 transition-colors">
                                        <i data-lucide="shield-check" class="w-4 h-4 text-neutral-600 dark:text-dark-200"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-neutral-900 dark:text-dark-50 text-sm">Academic Integrity</h3>
                                        <p class="text-xs text-neutral-500 dark:text-dark-200 mt-0.5">How should schools handle AI and academic honesty?</p>
                                    </div>
                                </div>
                            </button>
                            <button onclick="askQuestion('What are developmentally appropriate AI uses for different grade levels?')" class="text-left p-4 border border-neutral-200 dark:border-dark-600 rounded-xl hover:border-neutral-400 dark:hover:border-dark-500 hover:bg-neutral-50 dark:hover:bg-dark-800 transition-all group">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-neutral-100 dark:bg-dark-700 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-neutral-200 dark:group-hover:bg-dark-600 transition-colors">
                                        <i data-lucide="users" class="w-4 h-4 text-neutral-600 dark:text-dark-200"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-neutral-900 dark:text-dark-50 text-sm">Grade-Level Guidance</h3>
                                        <p class="text-xs text-neutral-500 dark:text-dark-200 mt-0.5">Age-appropriate AI uses from K-12</p>
                                    </div>
                                </div>
                            </button>
                            <button onclick="askQuestion('What does the LEA Getting Started checklist include?')" class="text-left p-4 border border-neutral-200 dark:border-dark-600 rounded-xl hover:border-neutral-400 dark:hover:border-dark-500 hover:bg-neutral-50 dark:hover:bg-dark-800 transition-all group">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-neutral-100 dark:bg-dark-700 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-neutral-200 dark:group-hover:bg-dark-600 transition-colors">
                                        <i data-lucide="clipboard-check" class="w-4 h-4 text-neutral-600 dark:text-dark-200"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-neutral-900 dark:text-dark-50 text-sm">Getting Started Checklist</h3>
                                        <p class="text-xs text-neutral-500 dark:text-dark-200 mt-0.5">Steps for LEAs to begin AI implementation</p>
                                    </div>
                                </div>
                            </button>
                            <button onclick="askQuestion('What are the key data privacy and security requirements?')" class="text-left p-4 border border-neutral-200 dark:border-dark-600 rounded-xl hover:border-neutral-400 dark:hover:border-dark-500 hover:bg-neutral-50 dark:hover:bg-dark-800 transition-all group">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-neutral-100 dark:bg-dark-700 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-neutral-200 dark:group-hover:bg-dark-600 transition-colors">
                                        <i data-lucide="lock" class="w-4 h-4 text-neutral-600 dark:text-dark-200"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-neutral-900 dark:text-dark-50 text-sm">Security & Privacy</h3>
                                        <p class="text-xs text-neutral-500 dark:text-dark-200 mt-0.5">FERPA, COPPA, and data protection guidance</p>
                                    </div>
                                </div>
                            </button>
                            <button onclick="askQuestion('How should schools engage families and communities about AI?')" class="text-left p-4 border border-neutral-200 dark:border-dark-600 rounded-xl hover:border-neutral-400 dark:hover:border-dark-500 hover:bg-neutral-50 dark:hover:bg-dark-800 transition-all group">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-neutral-100 dark:bg-dark-700 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-neutral-200 dark:group-hover:bg-dark-600 transition-colors">
                                        <i data-lucide="heart-handshake" class="w-4 h-4 text-neutral-600 dark:text-dark-200"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-neutral-900 dark:text-dark-50 text-sm">Family Engagement</h3>
                                        <p class="text-xs text-neutral-500 dark:text-dark-200 mt-0.5">Building community partnerships around AI</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                        <div class="text-center space-y-1">
                            <p class="text-xs text-neutral-400 dark:text-dark-300">Ask any question about the RIDE AI Guidance 2025 Framework</p>
                            <p class="text-xs text-neutral-400 dark:text-dark-400">Use of the input below is at your own discretion. <button onclick="openSettings()" class="underline hover:text-neutral-600 dark:hover:text-dark-200 transition-colors">View terms</button></p>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="border-t border-neutral-200 dark:border-dark-500 bg-neutral-100 dark:bg-dark-800 p-4">
                    <div class="max-w-3xl mx-auto">
                        <div class="message-box flex items-center bg-neutral-50 dark:bg-dark-700 border border-neutral-200 dark:border-dark-400 rounded-xl px-2 py-1 gap-2 focus-within:ring-2 focus-within:ring-blue-200 dark:focus-within:ring-dark-400 focus-within:border-transparent transition-all shadow-sm">
                            <div class="mode-toggle-wrapper relative flex-shrink-0">
                                <button id="modeToggleBtn" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium transition-all hover:bg-neutral-100 dark:hover:bg-dark-600 text-neutral-700 dark:text-dark-100" title="Switch Mode">
                                    <i id="modeIcon" data-lucide="graduation-cap" class="w-4 h-4"></i>
                                    <span id="modeLabel">Learn</span>
                                    <i data-lucide="chevron-down" class="w-3 h-3 opacity-50"></i>
                                </button>
                                <div id="modeDropdown" class="absolute bottom-full left-0 mb-2 w-56 bg-neutral-50 dark:bg-dark-700 border border-neutral-200 dark:border-dark-400 rounded-xl shadow-lg py-2 hidden z-50">
                                    <button id="learnModeBtn" class="mode-option w-full flex items-start gap-3 px-4 py-2.5 hover:bg-neutral-50 dark:hover:bg-dark-600 transition-colors text-left">
                                        <div class="w-8 h-8 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <i data-lucide="graduation-cap" class="w-4 h-4 text-accent-600 dark:text-accent-400"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-neutral-900 dark:text-dark-50">Learn</div>
                                            <div class="text-xs text-neutral-500 dark:text-dark-300">Explore & understand RIDE guidance</div>
                                        </div>
                                        <i id="learnCheck" data-lucide="check" class="w-4 h-4 text-accent-600 dark:text-accent-400 ml-auto mt-1"></i>
                                    </button>
                                    <button id="buildModeBtn" class="mode-option w-full flex items-start gap-3 px-4 py-2.5 hover:bg-neutral-50 dark:hover:bg-dark-600 transition-colors text-left">
                                        <div class="w-8 h-8 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <i data-lucide="hammer" class="w-4 h-4 text-accent-600 dark:text-accent-400"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-neutral-900 dark:text-dark-50">Build</div>
                                            <div class="text-xs text-neutral-500 dark:text-dark-300">Plan & implement for your district</div>
                                        </div>
                                        <i id="buildCheck" data-lucide="check" class="w-4 h-4 text-accent-600 dark:text-accent-400 ml-auto mt-1 hidden"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="w-px h-6 bg-neutral-200 dark:bg-dark-500 flex-shrink-0"></div>
                            <textarea id="messageInput" placeholder="Ask about the RIDE AI Guidance..." rows="1" class="flex-1 bg-transparent outline-none border-none resize-none text-sm text-neutral-800 dark:text-dark-50 placeholder-neutral-400 dark:placeholder-dark-300 py-2" style="max-height: 100px;"></textarea>
                            <button id="sendBtn" class="flex-shrink-0 w-9 h-9 bg-accent-500 dark:bg-accent-500 text-white rounded-lg hover:bg-accent-600 dark:hover:bg-accent-600 transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed" title="Send message">
                                <i data-lucide="arrow-up" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reference Panel -->
            <aside id="referencePanel" class="sidebar-panel w-[600px] border-l border-neutral-200 dark:border-dark-500 bg-neutral-50 dark:bg-dark-800 flex flex-col overflow-hidden">
                <div class="border-b border-neutral-200 dark:border-dark-600 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 text-neutral-600 dark:text-dark-200"></i>
                            <h2 class="font-semibold text-neutral-900 dark:text-dark-50">Reference Document</h2>
                        </div>
                        <a href="RIDE.pdf" target="_blank" class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-neutral-600 dark:text-dark-200 hover:text-neutral-900 dark:hover:text-dark-50 hover:bg-neutral-100 dark:hover:bg-dark-700 rounded-lg transition-colors" title="Open original PDF">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            <span>PDF</span>
                        </a>
                    </div>
                    <div class="flex gap-2">
                        <button id="tabGuide" class="tab-btn flex-1 px-3 py-2 text-xs font-medium rounded-lg bg-accent-500 dark:bg-accent-500 text-white transition-colors">Full Document</button>
                        <button id="tabSections" class="tab-btn flex-1 px-3 py-2 text-xs font-medium rounded-lg bg-neutral-100 dark:bg-dark-700 text-neutral-600 dark:text-dark-100 hover:bg-neutral-200 dark:hover:bg-dark-600 transition-colors">Quick Sections</button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <div id="guideContent" class="p-5 pl-6">
                        <div class="kb-content markdown-content text-sm">
                            <?php
                            $knowledgeBase = file_exists('knowledge-base.md') ? file_get_contents('knowledge-base.md') : '';
                            if ($knowledgeBase) {
                                function convertMarkdownTables($text) {
                                    $lines = explode("\n", $text);
                                    $result = [];
                                    $inTable = false;
                                    $tableRows = [];
                                    foreach ($lines as $line) {
                                        if (preg_match('/^\|(.+)\|$/', trim($line))) {
                                            if (preg_match('/^\|[\s\-:|]+\|$/', trim($line))) continue;
                                            if (!$inTable) { $inTable = true; $tableRows = []; }
                                            $tableRows[] = $line;
                                        } else {
                                            if ($inTable) { $result[] = buildHtmlTable($tableRows); $inTable = false; $tableRows = []; }
                                            $result[] = $line;
                                        }
                                    }
                                    if ($inTable) $result[] = buildHtmlTable($tableRows);
                                    return implode("\n", $result);
                                }
                                function buildHtmlTable($rows) {
                                    if (empty($rows)) return '';
                                    $html = '<table>';
                                    foreach ($rows as $i => $row) {
                                        $cells = array_map('trim', explode('|', trim($row, '|')));
                                        $tag = ($i === 0) ? 'th' : 'td';
                                        $html .= '<tr>';
                                        foreach ($cells as $cell) {
                                            $cell = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $cell);
                                            $html .= "<$tag>$cell</$tag>";
                                        }
                                        $html .= '</tr>';
                                    }
                                    $html .= '</table>';
                                    return $html;
                                }
                                $html = convertMarkdownTables($knowledgeBase);
                                $html = preg_replace_callback('/<(table|\/table|tr|\/tr|th|\/th|td|\/td|strong|\/strong)>/', function($m) { return '###' . $m[1] . '###'; }, $html);
                                $html = htmlspecialchars($html);
                                $html = preg_replace_callback('/###(table|\/table|tr|\/tr|th|\/th|td|\/td|strong|\/strong)###/', function($m) { return '<' . $m[1] . '>'; }, $html);
                                $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
                                $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
                                $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
                                $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
                                $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
                                $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
                                $html = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $html);
                                $html = preg_replace('/^---$/m', '<hr>', $html);
                                $html = preg_replace('/\n{2,}/', '</p><p>', $html);
                                $html = '<p>' . $html . '</p>';
                                $html = preg_replace('/<p>(<h[123]>)/', '$1', $html);
                                $html = preg_replace('/(<\/h[123]>)<\/p>/', '$1', $html);
                                $html = preg_replace('/<p>(<hr>)<\/p>/', '$1', $html);
                                $html = preg_replace('/<p>(<li>)/', '<ul>$1', $html);
                                $html = preg_replace('/(<\/li>)<\/p>/', '$1</ul>', $html);
                                $html = preg_replace('/<p>(<blockquote>)/', '$1', $html);
                                $html = preg_replace('/(<\/blockquote>)<\/p>/', '$1', $html);
                                $html = preg_replace('/<p>(<table>)/', '$1', $html);
                                $html = preg_replace('/(<\/table>)<\/p>/', '$1', $html);
                                echo $html;
                            } else {
                                echo '<p class="text-neutral-500">Knowledge base document not found. Please add knowledge-base.md to the v2 folder.</p>';
                            }
                            ?>
                        </div>
                    </div>
                    <div id="sectionsContent" class="p-4 hidden">
                        <div class="space-y-2">
                            <?php
                            $sections = [
                                ['1', 'Purpose & Context', 'Explain the Purpose and Context of the RIDE AI Guidance'],
                                ['2', 'Mission & Vision', 'What is the Mission and Vision for AI according to RIDE?'],
                                ['3', 'Strategic Alignment', 'How does AI align with RIDE strategic priorities?'],
                                ['4', 'Instructional Guidance', 'What is the Instructional Guidance for AI in classrooms?'],
                                ['5', 'Developmentally Appropriate Use', 'What are developmentally appropriate AI uses for different grade levels (K-2, 3-5, 6-8, 9-12)?'],
                                ['6', 'Academic Integrity', 'What does RIDE say about academic integrity and AI use? How should students cite AI?'],
                                ['7', 'Equity & Bias', 'What does RIDE say about Equity and Bias in AI?'],
                                ['8', 'Diverse Learners (DAS & MLL)', 'How should AI support Diverse Learners including ELL and IEP students?'],
                                ['9', 'Security & Safety', 'What are the Security and Safety requirements for AI in schools?'],
                                ['10', 'College & Career Readiness', 'How should AI support College and Career Readiness?'],
                                ['11', 'LEA Operations & Admin', 'What guidance exists for LEA Operations and Administration?'],
                                ['12', 'Family & Community', 'How should schools engage families and communities about AI?'],
                            ];
                            foreach ($sections as $s) {
                                echo '<button onclick="askQuestion(\'' . addslashes($s[2]) . '\')" class="w-full text-left p-3 border border-neutral-200 rounded-lg hover:border-neutral-400 hover:bg-neutral-50 transition-all dark:border-dark-600 dark:hover:border-dark-500 dark:hover:bg-dark-700">';
                                echo '<div class="flex items-center gap-3"><span class="w-6 h-6 bg-neutral-100 dark:bg-dark-600 rounded text-xs font-semibold flex items-center justify-center text-neutral-600 dark:text-dark-100">' . $s[0] . '</span>';
                                echo '<span class="text-sm font-medium text-neutral-800 dark:text-dark-50">' . $s[1] . '</span></div></button>';
                            }
                            ?>
                            <div class="border-t border-neutral-200 dark:border-dark-600 pt-3 mt-3">
                                <p class="text-xs font-semibold text-neutral-500 dark:text-dark-200 uppercase tracking-wide mb-2">Appendices</p>
                            </div>
                            <?php
                            $appendices = [
                                ['A', 'Conversation Starters', 'What conversation starters does RIDE provide for parents, teachers, and administrators?'],
                                ['B', 'Getting Started Checklist', 'What is included in the LEA Getting Started with AI checklist?'],
                                ['C', 'Procurement Checklist', 'What should be included in AI software procurement according to RIDE?'],
                                ['D', 'Sample Parent Letter', 'What is included in the sample parent letter about AI?'],
                            ];
                            foreach ($appendices as $a) {
                                echo '<button onclick="askQuestion(\'' . addslashes($a[2]) . '\')" class="w-full text-left p-3 border border-neutral-200 rounded-lg hover:border-neutral-400 hover:bg-neutral-50 transition-all dark:border-dark-600 dark:hover:border-dark-500 dark:hover:bg-dark-700">';
                                echo '<div class="flex items-center gap-3"><span class="w-6 h-6 bg-neutral-100 dark:bg-dark-600 rounded text-xs font-semibold flex items-center justify-center text-neutral-600 dark:text-dark-100">' . $a[0] . '</span>';
                                echo '<span class="text-sm font-medium text-neutral-800 dark:text-dark-50">' . $a[1] . '</span></div></button>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </aside>
        </main>
    </div>

    <!-- Settings Sidebar -->
    <div id="settingsModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/30" id="settingsOverlay"></div>
        <aside id="settingsSidebar" class="absolute top-0 left-0 h-full w-[400px] max-w-[90vw] bg-neutral-50 dark:bg-dark-800 shadow-2xl flex flex-col transform -translate-x-full transition-transform duration-300 ease-out">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-200 dark:border-dark-600 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-neutral-100 dark:bg-dark-700 rounded-lg flex items-center justify-center">
                        <i data-lucide="settings" class="w-4 h-4 text-neutral-600 dark:text-dark-200"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900 dark:text-dark-50">AI Transparency</h2>
                        <p class="text-xs text-neutral-500 dark:text-dark-300">How this assistant works</p>
                    </div>
                </div>
                <button id="closeSettingsBtn" class="p-2 hover:bg-neutral-100 dark:hover:bg-dark-700 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-neutral-600 dark:text-dark-200"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-5 space-y-5">
                <div>
                    <h3 class="text-sm font-semibold text-neutral-900 dark:text-dark-50 mb-3 flex items-center gap-2">
                        <i data-lucide="cpu" class="w-4 h-4"></i>
                        Model Settings (OpenAI Responses API)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-accent-50 dark:bg-accent-900/20 border border-accent-200 dark:border-accent-800 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <i data-lucide="graduation-cap" class="w-4 h-4 text-accent-600 dark:text-accent-400"></i>
                                <span class="font-medium text-accent-900 dark:text-accent-100">Learn Mode</span>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-accent-700 dark:text-accent-300">Model</span>
                                    <span class="font-mono text-accent-900 dark:text-accent-100">gpt-5.1</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-accent-700 dark:text-accent-300">Reasoning</span>
                                    <span class="font-mono text-accent-900 dark:text-accent-100">none</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-accent-700 dark:text-accent-300">Temperature</span>
                                    <span class="font-mono text-accent-900 dark:text-accent-100">0.7</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-accent-700 dark:text-accent-300">Top P</span>
                                    <span class="font-mono text-accent-900 dark:text-accent-100">0.9</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-accent-700 dark:text-accent-300">Verbosity</span>
                                    <span class="font-mono text-accent-900 dark:text-accent-100">low</span>
                                </div>
                            </div>
                            <p class="text-xs text-accent-600 dark:text-accent-400 mt-3">Creative sampling for conversational explanations</p>
                        </div>
                        <div class="bg-accent-50 dark:bg-accent-900/20 border border-accent-200 dark:border-accent-800 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <i data-lucide="hammer" class="w-4 h-4 text-accent-600 dark:text-accent-400"></i>
                                <span class="font-medium text-accent-900 dark:text-accent-100">Build Mode</span>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-accent-700 dark:text-accent-300">Model</span>
                                    <span class="font-mono text-accent-900 dark:text-accent-100">gpt-5.1</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-accent-700 dark:text-accent-300">Reasoning</span>
                                    <span class="font-mono text-accent-900 dark:text-accent-100">medium</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-accent-700 dark:text-accent-300">Verbosity</span>
                                    <span class="font-mono text-accent-900 dark:text-accent-100">medium</span>
                                </div>
                            </div>
                            <p class="text-xs text-accent-600 dark:text-accent-400 mt-3">Thorough reasoning for policy development</p>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-neutral-900 dark:text-dark-50 mb-3 flex items-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        System Prompts (What the AI is told)
                    </h3>
                    <div class="mb-4">
                        <button id="toggleLearnPrompt" class="w-full flex items-center justify-between p-3 bg-accent-50 dark:bg-accent-900/20 border border-accent-200 dark:border-accent-800 rounded-lg hover:bg-accent-100 dark:hover:bg-accent-900/30 transition-colors">
                            <div class="flex items-center gap-2">
                                <i data-lucide="graduation-cap" class="w-4 h-4 text-accent-600 dark:text-accent-400"></i>
                                <span class="font-medium text-accent-900 dark:text-accent-100">Learn Mode Prompt</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-accent-600 dark:text-accent-400 learn-chevron transition-transform"></i>
                        </button>
                        <div id="learnPromptContent" class="hidden mt-2 p-4 bg-neutral-50 dark:bg-dark-900 border border-neutral-200 dark:border-dark-600 rounded-lg">
                            <pre class="text-xs text-neutral-700 dark:text-dark-200 whitespace-pre-wrap font-mono leading-relaxed overflow-x-auto">LEARN MODE - Educational Focus

Writing in flowing paragraphs with narrative style.
Strategic formatting with bold key concepts and blockquotes.

Response approach:
1. Open with context
2. Use narrative flow
3. Embed examples naturally
4. Include 2-3 blockquotes per response
5. End with "What This Means for You"</pre>
                        </div>
                    </div>
                    <div>
                        <button id="toggleBuildPrompt" class="w-full flex items-center justify-between p-3 bg-accent-50 dark:bg-accent-900/20 border border-accent-200 dark:border-accent-800 rounded-lg hover:bg-accent-100 dark:hover:bg-accent-900/30 transition-colors">
                            <div class="flex items-center gap-2">
                                <i data-lucide="hammer" class="w-4 h-4 text-accent-600 dark:text-accent-400"></i>
                                <span class="font-medium text-accent-900 dark:text-accent-100">Build Mode Prompt</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-accent-600 dark:text-accent-400 build-chevron transition-transform"></i>
                        </button>
                        <div id="buildPromptContent" class="hidden mt-2 p-4 bg-neutral-50 dark:bg-dark-900 border border-neutral-200 dark:border-dark-600 rounded-lg">
                            <pre class="text-xs text-neutral-700 dark:text-dark-200 whitespace-pre-wrap font-mono leading-relaxed overflow-x-auto">BUILD MODE - Implementation Focus

Strategic partner for policy development.

Response format:
- Understanding Check
- Relevant RIDE Guidance (cite sections)
- Actionable Recommendations (numbered)
- Draft Content (templates, policies)
- Next Steps (clear actions)</pre>
                        </div>
                    </div>
                </div>
                <div class="bg-neutral-100 dark:bg-dark-700 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="database" class="w-5 h-5 text-neutral-600 dark:text-dark-200 mt-0.5"></i>
                        <div>
                            <h4 class="font-medium text-neutral-900 dark:text-dark-50 mb-1">Knowledge Base</h4>
                            <p class="text-sm text-neutral-600 dark:text-dark-300">This assistant uses the <strong>RIDE AI Guidance 2025</strong> document. All responses are grounded in this official Rhode Island framework.</p>
                        </div>
                    </div>
                </div>

                <!-- Disclaimer -->
                <div class="border-t border-neutral-200 dark:border-dark-600 pt-4 mt-2">
                    <div class="flex items-start gap-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-neutral-400 dark:text-dark-400 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <h4 class="font-medium text-neutral-700 dark:text-dark-200 mb-1 text-sm">Disclaimer</h4>
                            <p class="text-xs text-neutral-500 dark:text-dark-400 leading-relaxed">This AI assistant is provided for <strong>informational purposes only</strong>. Responses are generated by artificial intelligence and may contain errors, omissions, or inaccuracies. Users are solely responsible for verifying all information and independently evaluating any guidance before making decisions or implementing policies.</p>
                            <p class="text-xs text-neutral-500 dark:text-dark-400 leading-relaxed mt-2">The developers, operators, and data providers of this tool make <strong>no warranties</strong>, express or implied, regarding the accuracy, completeness, or suitability of any content. By using this service, you acknowledge that you do so <strong>at your own risk</strong> and that neither the developers nor the source documentation shall be held liable for any outcomes resulting from the use of this tool.</p>
                            <p class="text-xs text-neutral-400 dark:text-dark-500 mt-2 italic">Use of this tool constitutes acceptance of these terms.</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- District Profile Sidebar -->
    <div id="districtModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/30" id="districtOverlay"></div>
        <aside id="districtSidebar" class="absolute top-0 left-0 h-full w-[480px] max-w-[95vw] bg-neutral-50 dark:bg-dark-800 shadow-2xl flex flex-col transform -translate-x-full transition-transform duration-300 ease-out">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-200 dark:border-dark-600 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center">
                        <i data-lucide="building-2" class="w-4 h-4 text-accent-600 dark:text-accent-400"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900 dark:text-dark-50">District Profile</h2>
                        <p class="text-xs text-neutral-500 dark:text-dark-300">Help the AI understand your context</p>
                    </div>
                </div>
                <button id="closeDistrictBtn" class="p-2 hover:bg-neutral-100 dark:hover:bg-dark-700 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-neutral-600 dark:text-dark-200"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-5 space-y-5">
                <div class="bg-accent-50 dark:bg-accent-900/20 border border-accent-200 dark:border-accent-800 rounded-xl p-4">
                    <div class="flex gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-accent-600 dark:text-accent-400 flex-shrink-0 mt-0.5"></i>
                        <p class="text-sm text-accent-800 dark:text-accent-200">This information helps the AI provide tailored guidance. Your data is stored locally in your browser.</p>
                    </div>
                </div>
                <form id="districtForm" class="space-y-5">
                    <div>
                        <label for="districtName" class="block text-sm font-medium text-neutral-900 dark:text-dark-50 mb-1.5">District Name</label>
                        <input type="text" id="districtName" name="districtName" placeholder="e.g., Providence Public Schools" class="w-full px-3 py-2.5 text-sm border border-neutral-200 dark:border-dark-500 rounded-lg bg-white dark:bg-dark-700 text-neutral-900 dark:text-dark-50 placeholder-neutral-400 dark:placeholder-dark-300 focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all">
                    </div>
                    <div>
                        <label for="userRole" class="block text-sm font-medium text-neutral-900 dark:text-dark-50 mb-1.5">Your Role</label>
                        <select id="userRole" name="userRole" class="w-full px-3 py-2.5 text-sm border border-neutral-200 dark:border-dark-500 rounded-lg bg-white dark:bg-dark-700 text-neutral-900 dark:text-dark-50 focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all">
                            <option value="">Select your role...</option>
                            <option value="superintendent">Superintendent</option>
                            <option value="assistant_superintendent">Assistant Superintendent</option>
                            <option value="principal">Principal</option>
                            <option value="curriculum_director">Curriculum Director</option>
                            <option value="technology_director">Technology Director/Coordinator</option>
                            <option value="teacher_leader">Teacher Leader</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="districtSize" class="block text-sm font-medium text-neutral-900 dark:text-dark-50 mb-1.5">District Size</label>
                        <select id="districtSize" name="districtSize" class="w-full px-3 py-2.5 text-sm border border-neutral-200 dark:border-dark-500 rounded-lg bg-white dark:bg-dark-700 text-neutral-900 dark:text-dark-50 focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all">
                            <option value="">Select size...</option>
                            <option value="small">Small (under 1,000 students)</option>
                            <option value="medium">Medium (1,000 - 7,000 students)</option>
                            <option value="large">Large (7,000+ students)</option>
                        </select>
                    </div>
                    <div>
                        <label for="policyStatus" class="block text-sm font-medium text-neutral-900 dark:text-dark-50 mb-1.5">AI Policy Status</label>
                        <select id="policyStatus" name="policyStatus" class="w-full px-3 py-2.5 text-sm border border-neutral-200 dark:border-dark-500 rounded-lg bg-white dark:bg-dark-700 text-neutral-900 dark:text-dark-50 focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all">
                            <option value="">Select status...</option>
                            <option value="none">No AI policy yet</option>
                            <option value="exploring">Exploring options</option>
                            <option value="drafting">Currently drafting</option>
                            <option value="implemented">Have existing policy</option>
                        </select>
                    </div>
                    <div>
                        <label for="challenges" class="block text-sm font-medium text-neutral-900 dark:text-dark-50 mb-1.5">Key Challenges</label>
                        <textarea id="challenges" name="challenges" rows="3" placeholder="What challenges are you facing with AI implementation?" class="w-full px-3 py-2.5 text-sm border border-neutral-200 dark:border-dark-500 rounded-lg bg-white dark:bg-dark-700 text-neutral-900 dark:text-dark-50 placeholder-neutral-400 dark:placeholder-dark-300 focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all resize-none"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium rounded-lg transition-colors">Save Profile</button>
                        <button type="button" id="clearDistrictBtn" class="px-4 py-2.5 border border-neutral-200 dark:border-dark-500 text-neutral-600 dark:text-dark-200 text-sm font-medium rounded-lg hover:bg-neutral-50 dark:hover:bg-dark-700 transition-colors">Clear</button>
                    </div>
                </form>
            </div>
        </aside>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2"></div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 z-[90] hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="confirmOverlay"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div id="confirmDialog" class="bg-neutral-50 dark:bg-dark-800 rounded-2xl shadow-2xl w-full max-w-sm transform scale-95 opacity-0 transition-all duration-200">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div id="confirmIcon" class="w-10 h-10 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-accent-600 dark:text-accent-400"></i>
                        </div>
                        <h3 id="confirmTitle" class="text-lg font-semibold text-neutral-900 dark:text-dark-50">Confirm Action</h3>
                    </div>
                    <p id="confirmMessage" class="text-sm text-neutral-600 dark:text-dark-300 mb-6">Are you sure you want to proceed?</p>
                    <div class="flex gap-3">
                        <button id="confirmCancel" class="flex-1 px-4 py-2.5 text-sm font-medium text-neutral-700 dark:text-dark-200 bg-neutral-100 dark:bg-dark-700 hover:bg-neutral-200 dark:hover:bg-dark-600 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button id="confirmAction" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700 rounded-lg transition-colors">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // DOM Elements
        const chatMessages = document.getElementById('chatMessages');
        const chatContent = document.getElementById('chatContent');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const clearChatBtn = document.getElementById('clearChatBtn');
        const welcomeScreen = document.getElementById('welcomeScreen');
        const darkModeBtn = document.getElementById('darkModeBtn');
        const togglePanelBtn = document.getElementById('togglePanelBtn');
        const referencePanel = document.getElementById('referencePanel');
        const tabGuide = document.getElementById('tabGuide');
        const tabSections = document.getElementById('tabSections');
        const guideContent = document.getElementById('guideContent');
        const sectionsContent = document.getElementById('sectionsContent');
        const settingsBtn = document.getElementById('settingsBtn');
        const settingsModal = document.getElementById('settingsModal');
        const settingsSidebar = document.getElementById('settingsSidebar');
        const settingsOverlay = document.getElementById('settingsOverlay');
        const closeSettingsBtn = document.getElementById('closeSettingsBtn');
        const modeToggleBtn = document.getElementById('modeToggleBtn');
        const modeDropdown = document.getElementById('modeDropdown');
        const modeIcon = document.getElementById('modeIcon');
        const modeLabel = document.getElementById('modeLabel');
        const learnModeBtn = document.getElementById('learnModeBtn');
        const buildModeBtn = document.getElementById('buildModeBtn');
        const learnCheck = document.getElementById('learnCheck');
        const buildCheck = document.getElementById('buildCheck');
        const districtProfileBtn = document.getElementById('districtProfileBtn');
        const districtModal = document.getElementById('districtModal');
        const districtSidebar = document.getElementById('districtSidebar');
        const districtOverlay = document.getElementById('districtOverlay');
        const closeDistrictBtn = document.getElementById('closeDistrictBtn');
        const districtForm = document.getElementById('districtForm');
        const clearDistrictBtn = document.getElementById('clearDistrictBtn');
        const districtProfileBadge = document.getElementById('districtProfileBadge');
        const toggleLearnPrompt = document.getElementById('toggleLearnPrompt');
        const toggleBuildPrompt = document.getElementById('toggleBuildPrompt');
        const learnPromptContent = document.getElementById('learnPromptContent');
        const buildPromptContent = document.getElementById('buildPromptContent');

        // State
        let conversationHistory = [];
        let isGenerating = false;
        let currentMode = 'learn';
        let panelVisible = true;
        let districtProfile = JSON.parse(localStorage.getItem('districtProfile') || '{}');
        let abortController = null; // For canceling streaming requests

        // Chat persistence functions
        function saveChatToStorage() {
            const chatData = {
                history: conversationHistory,
                mode: currentMode,
                timestamp: Date.now()
            };
            localStorage.setItem('rideChat', JSON.stringify(chatData));
        }

        function loadChatFromStorage() {
            try {
                const saved = localStorage.getItem('rideChat');
                if (!saved) return false;

                const chatData = JSON.parse(saved);
                // Only load if less than 24 hours old
                if (Date.now() - chatData.timestamp > 24 * 60 * 60 * 1000) {
                    localStorage.removeItem('rideChat');
                    return false;
                }

                if (chatData.history && chatData.history.length > 0) {
                    conversationHistory = chatData.history;
                    currentMode = chatData.mode || 'learn';
                    return true;
                }
            } catch (e) {
                console.error('Failed to load chat:', e);
            }
            return false;
        }

        function restoreChatUI() {
            welcomeScreen.classList.add('hidden');
            conversationHistory.forEach(msg => {
                if (msg.role === 'user') {
                    addMessage(msg.content, 'user');
                } else if (msg.role === 'assistant') {
                    addAssistantMessage(msg.content);
                }
            });
            // Update mode UI silently (don't show "Switched to X Mode" toast)
            setMode(currentMode, true);
        }

        function clearChatStorage() {
            localStorage.removeItem('rideChat');
        }

        // Copy to clipboard function - copies both HTML (formatted) and plain text
        async function copyToClipboard(text, button) {
            try {
                // Convert markdown to HTML for rich text pasting
                const htmlContent = marked.parse(text);

                // Create clipboard items with both formats
                const clipboardItem = new ClipboardItem({
                    'text/html': new Blob([htmlContent], { type: 'text/html' }),
                    'text/plain': new Blob([text], { type: 'text/plain' })
                });

                await navigator.clipboard.write([clipboardItem]);

                // Show success feedback
                const icon = button.querySelector('svg') || button.querySelector('i');
                const originalIcon = icon.getAttribute('data-lucide');
                icon.setAttribute('data-lucide', 'check');
                lucide.createIcons();
                showToast('Copied with formatting', 'success', 2000);
                setTimeout(() => {
                    const newIcon = button.querySelector('svg') || button.querySelector('i');
                    if (newIcon) {
                        newIcon.setAttribute('data-lucide', originalIcon);
                        lucide.createIcons();
                    }
                }, 2000);
            } catch (err) {
                // Fallback to plain text if ClipboardItem not supported
                try {
                    await navigator.clipboard.writeText(text);
                    const icon = button.querySelector('svg') || button.querySelector('i');
                    const originalIcon = icon.getAttribute('data-lucide');
                    icon.setAttribute('data-lucide', 'check');
                    lucide.createIcons();
                    showToast('Copied to clipboard', 'success', 2000);
                    setTimeout(() => {
                        const newIcon = button.querySelector('svg') || button.querySelector('i');
                        if (newIcon) {
                            newIcon.setAttribute('data-lucide', originalIcon);
                            lucide.createIcons();
                        }
                    }, 2000);
                } catch (fallbackErr) {
                    showToast('Failed to copy', 'error');
                }
            }
        }

        // Initialize
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
        updateDistrictBadge();
        loadDistrictForm();

        // Toast notification system
        const toastContainer = document.getElementById('toastContainer');

        function showToast(message, type = 'info', duration = 4000) {
            const toast = document.createElement('div');
            const icons = {
                success: 'check-circle',
                error: 'x-circle',
                warning: 'alert-triangle',
                info: 'info'
            };
            const colors = {
                success: 'bg-accent-50 dark:bg-accent-900/30 border-accent-200 dark:border-accent-800 text-accent-800 dark:text-accent-200',
                error: 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
                warning: 'bg-accent-50 dark:bg-accent-900/30 border-accent-200 dark:border-accent-800 text-accent-800 dark:text-accent-200',
                info: 'bg-accent-50 dark:bg-accent-900/30 border-accent-200 dark:border-accent-800 text-accent-800 dark:text-accent-200'
            };
            const iconColors = {
                success: 'text-accent-500 dark:text-accent-400',
                error: 'text-red-500 dark:text-red-400',
                warning: 'text-accent-500 dark:text-accent-400',
                info: 'text-accent-500 dark:text-accent-400'
            };

            toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl border shadow-lg transform translate-x-full opacity-0 transition-all duration-300 ${colors[type]}`;
            toast.innerHTML = `
                <i data-lucide="${icons[type]}" class="w-5 h-5 flex-shrink-0 ${iconColors[type]}"></i>
                <span class="text-sm font-medium">${message}</span>
                <button class="ml-auto p-1 hover:bg-black/5 dark:hover:bg-white/5 rounded-lg transition-colors" onclick="this.parentElement.remove()">
                    <i data-lucide="x" class="w-4 h-4 opacity-60"></i>
                </button>
            `;

            toastContainer.appendChild(toast);
            lucide.createIcons();

            // Animate in
            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            });

            // Auto remove
            if (duration > 0) {
                setTimeout(() => {
                    toast.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }

            return toast;
        }

        // Custom confirmation modal
        const confirmModal = document.getElementById('confirmModal');
        const confirmDialog = document.getElementById('confirmDialog');
        const confirmOverlay = document.getElementById('confirmOverlay');
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmCancel = document.getElementById('confirmCancel');
        const confirmActionBtn = document.getElementById('confirmAction');
        let confirmResolve = null;

        function showConfirm(message, options = {}) {
            const {
                title = 'Confirm Action',
                confirmText = 'Confirm',
                cancelText = 'Cancel',
                type = 'warning'
            } = options;

            confirmTitle.textContent = title;
            confirmMessage.textContent = message;
            confirmCancel.textContent = cancelText;
            confirmActionBtn.textContent = confirmText;

            // Update button style based on type
            if (type === 'danger') {
                confirmActionBtn.className = 'flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors';
            } else {
                confirmActionBtn.className = 'flex-1 px-4 py-2.5 text-sm font-medium text-white bg-accent-500 hover:bg-accent-600 rounded-lg transition-colors';
            }

            confirmModal.classList.remove('hidden');
            lucide.createIcons();

            requestAnimationFrame(() => {
                confirmDialog.classList.remove('scale-95', 'opacity-0');
                confirmDialog.classList.add('scale-100', 'opacity-100');
            });

            return new Promise((resolve) => {
                confirmResolve = resolve;
            });
        }

        function closeConfirm(result) {
            confirmDialog.classList.remove('scale-100', 'opacity-100');
            confirmDialog.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                confirmModal.classList.add('hidden');
                if (confirmResolve) {
                    confirmResolve(result);
                    confirmResolve = null;
                }
            }, 200);
        }

        confirmCancel.addEventListener('click', () => closeConfirm(false));
        confirmOverlay.addEventListener('click', () => closeConfirm(false));
        confirmActionBtn.addEventListener('click', () => closeConfirm(true));

        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // Dark mode toggle
        darkModeBtn.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('darkMode', isDark);
            showToast(isDark ? 'Dark mode enabled' : 'Light mode enabled', 'info');
        });

        // Mode toggle
        modeToggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            modeDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', function() {
            modeDropdown.classList.add('hidden');
        });

        learnModeBtn.addEventListener('click', function() {
            setMode('learn');
            modeDropdown.classList.add('hidden');
        });

        buildModeBtn.addEventListener('click', function() {
            setMode('build');
            modeDropdown.classList.add('hidden');
        });

        function setMode(mode, silent = false) {
            currentMode = mode;
            if (mode === 'learn') {
                modeIcon.setAttribute('data-lucide', 'graduation-cap');
                modeLabel.textContent = 'Learn';
                learnCheck.classList.remove('hidden');
                buildCheck.classList.add('hidden');
                if (!silent) showToast('Switched to Learn Mode', 'info');
            } else {
                modeIcon.setAttribute('data-lucide', 'hammer');
                modeLabel.textContent = 'Build';
                learnCheck.classList.add('hidden');
                buildCheck.classList.remove('hidden');
                if (!silent) showToast('Switched to Build Mode', 'info');
            }
            lucide.createIcons();
        }

        // Restore chat if available (must be after setMode is defined)
        if (loadChatFromStorage()) {
            restoreChatUI();
            showToast('Previous conversation restored', 'info', 3000);
        }

        // Settings modal
        settingsBtn.addEventListener('click', function() {
            settingsModal.classList.remove('hidden');
            setTimeout(() => settingsSidebar.classList.remove('-translate-x-full'), 10);
        });

        function closeSettings() {
            settingsSidebar.classList.add('-translate-x-full');
            setTimeout(() => settingsModal.classList.add('hidden'), 300);
        }

        function openSettings() {
            settingsModal.classList.remove('hidden');
            setTimeout(() => settingsSidebar.classList.remove('-translate-x-full'), 10);
        }

        closeSettingsBtn.addEventListener('click', closeSettings);
        settingsOverlay.addEventListener('click', closeSettings);

        toggleLearnPrompt.addEventListener('click', function() {
            learnPromptContent.classList.toggle('hidden');
            this.querySelector('.learn-chevron').classList.toggle('rotate-180');
        });

        toggleBuildPrompt.addEventListener('click', function() {
            buildPromptContent.classList.toggle('hidden');
            this.querySelector('.build-chevron').classList.toggle('rotate-180');
        });

        // District profile modal
        districtProfileBtn.addEventListener('click', function() {
            districtModal.classList.remove('hidden');
            setTimeout(() => districtSidebar.classList.remove('-translate-x-full'), 10);
        });

        function closeDistrict() {
            districtSidebar.classList.add('-translate-x-full');
            setTimeout(() => districtModal.classList.add('hidden'), 300);
        }

        closeDistrictBtn.addEventListener('click', closeDistrict);
        districtOverlay.addEventListener('click', closeDistrict);

        districtForm.addEventListener('submit', function(e) {
            e.preventDefault();
            districtProfile = {
                name: document.getElementById('districtName').value,
                role: document.getElementById('userRole').value,
                size: document.getElementById('districtSize').value,
                policyStatus: document.getElementById('policyStatus').value,
                challenges: document.getElementById('challenges').value
            };
            localStorage.setItem('districtProfile', JSON.stringify(districtProfile));
            updateDistrictBadge();
            closeDistrict();
            showToast('District profile saved', 'success');
        });

        clearDistrictBtn.addEventListener('click', async function() {
            const confirmed = await showConfirm('Clear your district profile information?', {
                title: 'Clear Profile',
                confirmText: 'Clear',
                cancelText: 'Keep',
                type: 'danger'
            });
            if (confirmed) {
                districtProfile = {};
                localStorage.removeItem('districtProfile');
                document.getElementById('districtName').value = '';
                document.getElementById('userRole').value = '';
                document.getElementById('districtSize').value = '';
                document.getElementById('policyStatus').value = '';
                document.getElementById('challenges').value = '';
                updateDistrictBadge();
                showToast('Profile cleared', 'info');
            }
        });

        function loadDistrictForm() {
            if (districtProfile.name) document.getElementById('districtName').value = districtProfile.name;
            if (districtProfile.role) document.getElementById('userRole').value = districtProfile.role;
            if (districtProfile.size) document.getElementById('districtSize').value = districtProfile.size;
            if (districtProfile.policyStatus) document.getElementById('policyStatus').value = districtProfile.policyStatus;
            if (districtProfile.challenges) document.getElementById('challenges').value = districtProfile.challenges;
        }

        function updateDistrictBadge() {
            if (districtProfile.name || districtProfile.role) {
                districtProfileBadge.classList.remove('hidden');
            } else {
                districtProfileBadge.classList.add('hidden');
            }
        }

        function formatDistrictContext() {
            if (!districtProfile.name && !districtProfile.role) return null;
            let context = '';
            if (districtProfile.name) context += `District: ${districtProfile.name}\n`;
            if (districtProfile.role) context += `Role: ${districtProfile.role}\n`;
            if (districtProfile.size) context += `Size: ${districtProfile.size}\n`;
            if (districtProfile.policyStatus) context += `AI Policy Status: ${districtProfile.policyStatus}\n`;
            if (districtProfile.challenges) context += `Key Challenges: ${districtProfile.challenges}\n`;
            return context;
        }

        // Send on Enter
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        sendBtn.addEventListener('click', function() {
            if (isGenerating) {
                stopGeneration();
            } else {
                sendMessage();
            }
        });

        function stopGeneration() {
            if (abortController) {
                abortController.abort();
                abortController = null;
            }
        }

        function updateSendButton(generating) {
            // Lucide replaces <i> with <svg>, so we need to check for both
            let icon = sendBtn.querySelector('svg') || sendBtn.querySelector('i');

            if (generating) {
                sendBtn.classList.remove('bg-accent-500', 'hover:bg-accent-600', 'dark:bg-accent-500', 'dark:hover:bg-accent-600');
                sendBtn.classList.add('bg-red-500', 'hover:bg-red-600', 'dark:bg-red-500', 'dark:hover:bg-red-600');
                sendBtn.title = 'Stop generating';
                // Replace the icon with a new one
                if (icon) {
                    const newIcon = document.createElement('i');
                    newIcon.setAttribute('data-lucide', 'square');
                    newIcon.className = icon.className || 'w-4 h-4';
                    icon.replaceWith(newIcon);
                }
                sendBtn.disabled = false;
            } else {
                sendBtn.classList.remove('bg-red-500', 'hover:bg-red-600', 'dark:bg-red-500', 'dark:hover:bg-red-600');
                sendBtn.classList.add('bg-accent-500', 'hover:bg-accent-600', 'dark:bg-accent-500', 'dark:hover:bg-accent-600');
                sendBtn.title = 'Send message';
                // Replace the icon with a new one
                icon = sendBtn.querySelector('svg') || sendBtn.querySelector('i');
                if (icon) {
                    const newIcon = document.createElement('i');
                    newIcon.setAttribute('data-lucide', 'arrow-up');
                    newIcon.className = 'w-4 h-4';
                    icon.replaceWith(newIcon);
                }
            }
            lucide.createIcons();
        }

        clearChatBtn.addEventListener('click', async function() {
            const confirmed = await showConfirm('This will clear the entire conversation. This action cannot be undone.', {
                title: 'Clear Conversation',
                confirmText: 'Clear',
                cancelText: 'Keep',
                type: 'danger'
            });
            if (confirmed) {
                conversationHistory = [];
                clearChatStorage();
                chatContent.innerHTML = '';
                chatContent.appendChild(welcomeScreen);
                welcomeScreen.classList.remove('hidden');
                lucide.createIcons();
                showToast('Conversation cleared', 'success');
            }
        });

        togglePanelBtn.addEventListener('click', function() {
            panelVisible = !panelVisible;
            if (panelVisible) {
                referencePanel.classList.remove('collapsed');
            } else {
                referencePanel.classList.add('collapsed');
            }
        });

        tabGuide.addEventListener('click', function() { setActiveTab('guide'); });
        tabSections.addEventListener('click', function() { setActiveTab('sections'); });

        function setActiveTab(tab) {
            const activeClasses = ['bg-accent-500', 'dark:bg-accent-500', 'text-white'];
            const inactiveClasses = ['bg-neutral-100', 'dark:bg-dark-700', 'text-neutral-600', 'dark:text-dark-100'];
            if (tab === 'guide') {
                tabGuide.classList.remove(...inactiveClasses);
                tabGuide.classList.add(...activeClasses);
                tabSections.classList.remove(...activeClasses);
                tabSections.classList.add(...inactiveClasses);
                guideContent.classList.remove('hidden');
                sectionsContent.classList.add('hidden');
            } else {
                tabSections.classList.remove(...inactiveClasses);
                tabSections.classList.add(...activeClasses);
                tabGuide.classList.remove(...activeClasses);
                tabGuide.classList.add(...inactiveClasses);
                sectionsContent.classList.remove('hidden');
                guideContent.classList.add('hidden');
            }
        }

        function askQuestion(question) {
            messageInput.value = question;
            sendMessage();
        }

        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message || isGenerating) return;

            isGenerating = true;
            abortController = new AbortController();
            updateSendButton(true);
            messageInput.value = '';
            messageInput.style.height = 'auto';

            welcomeScreen.classList.add('hidden');
            addMessage(message, 'user');
            conversationHistory.push({ role: 'user', content: message });
            saveChatToStorage(); // Save after user message

            const streamingDiv = createStreamingMessage();
            let fullResponse = '';
            let wasStopped = false;

            try {
                const districtContext = formatDistrictContext();
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: message,
                        mode: currentMode,
                        history: conversationHistory.slice(0, -1),
                        stream: true,
                        districtContext: districtContext
                    }),
                    signal: abortController.signal
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Server error');
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value, { stream: true });
                    const lines = chunk.split('\n');

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            const data = line.slice(6);
                            if (data === '[DONE]') continue;

                            try {
                                const parsed = JSON.parse(data);
                                if (parsed.content) {
                                    fullResponse += parsed.content;
                                    updateStreamingMessage(streamingDiv, fullResponse);
                                }
                                if (parsed.error) {
                                    throw new Error(parsed.error);
                                }
                            } catch (e) {
                                // Skip invalid JSON
                            }
                        }
                    }
                }

                finalizeStreamingMessage(streamingDiv, fullResponse);
                conversationHistory.push({ role: 'assistant', content: fullResponse });
                saveChatToStorage(); // Save after assistant response

            } catch (error) {
                if (error.name === 'AbortError') {
                    wasStopped = true;
                    if (fullResponse) {
                        // Keep partial response and mark as stopped
                        finalizeStreamingMessage(streamingDiv, fullResponse + '\n\n*[Response stopped by user]*');
                        conversationHistory.push({ role: 'assistant', content: fullResponse });
                        saveChatToStorage();
                        showToast('Response stopped', 'info');
                    } else {
                        streamingDiv.remove();
                        showToast('Response cancelled', 'info');
                    }
                } else if (fullResponse) {
                    finalizeStreamingMessage(streamingDiv, fullResponse);
                    conversationHistory.push({ role: 'assistant', content: fullResponse });
                    saveChatToStorage();
                } else {
                    streamingDiv.remove();
                    addMessage('Error: ' + error.message, 'error');
                    showToast('Failed to get response', 'error');
                }
                if (!wasStopped) console.error('Error:', error);
            }

            isGenerating = false;
            abortController = null;
            updateSendButton(false);
            messageInput.focus();
        }

        function createStreamingMessage() {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-4 message-animate message-wrapper';
            messageDiv.innerHTML = `
                <div class="flex gap-3">
                    <div class="w-8 h-8 bg-neutral-100 dark:bg-dark-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-lucide="bot" class="w-4 h-4 text-neutral-600 dark:text-dark-100"></i>
                    </div>
                    <div class="flex-1 max-w-[85%]">
                        <div class="streaming-content markdown-content text-sm text-neutral-800 dark:text-dark-50">
                            <div class="flex items-center gap-1.5 py-2 text-neutral-400 dark:text-dark-300">
                                <span class="typing-dot"></span>
                                <span class="typing-dot"></span>
                                <span class="typing-dot"></span>
                                <span class="ml-2 text-xs">Thinking...</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            chatContent.appendChild(messageDiv);
            lucide.createIcons();
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return messageDiv;
        }

        function updateStreamingMessage(messageDiv, content) {
            const contentDiv = messageDiv.querySelector('.streaming-content');
            contentDiv.innerHTML = marked.parse(content) + '<span class="inline-block w-2 h-4 bg-neutral-400 dark:bg-dark-400 animate-pulse ml-1"></span>';
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function finalizeStreamingMessage(messageDiv, content) {
            const contentDiv = messageDiv.querySelector('.streaming-content');
            // Store raw content for copy functionality
            messageDiv.dataset.rawContent = content;
            contentDiv.innerHTML = marked.parse(content);

            // Add copy button container after content
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'message-actions mt-2 flex gap-2';
            actionsDiv.innerHTML = `
                <button class="copy-btn flex items-center gap-1 px-2 py-1 text-xs text-neutral-500 dark:text-dark-300 hover:text-neutral-700 dark:hover:text-dark-100 hover:bg-neutral-100 dark:hover:bg-dark-600 rounded transition-colors" title="Copy response">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    <span>Copy</span>
                </button>
            `;
            contentDiv.appendChild(actionsDiv);

            // Attach copy handler
            const copyBtn = actionsDiv.querySelector('.copy-btn');
            copyBtn.addEventListener('click', () => copyToClipboard(content, copyBtn));

            lucide.createIcons();
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Add assistant message (used for restoring from localStorage)
        function addAssistantMessage(content) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-4 message-animate message-wrapper';
            messageDiv.dataset.rawContent = content;
            messageDiv.innerHTML = `
                <div class="flex gap-3">
                    <div class="w-8 h-8 bg-neutral-100 dark:bg-dark-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-lucide="bot" class="w-4 h-4 text-neutral-600 dark:text-dark-100"></i>
                    </div>
                    <div class="flex-1 max-w-[85%]">
                        <div class="streaming-content markdown-content text-sm text-neutral-800 dark:text-dark-50">
                            ${marked.parse(content)}
                            <div class="message-actions mt-2 flex gap-2">
                                <button class="copy-btn flex items-center gap-1 px-2 py-1 text-xs text-neutral-500 dark:text-dark-300 hover:text-neutral-700 dark:hover:text-dark-100 hover:bg-neutral-100 dark:hover:bg-dark-600 rounded transition-colors" title="Copy response">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span>Copy</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            chatContent.appendChild(messageDiv);

            // Attach copy handler
            const copyBtn = messageDiv.querySelector('.copy-btn');
            copyBtn.addEventListener('click', () => copyToClipboard(content, copyBtn));

            lucide.createIcons();
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function addMessage(content, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-4 message-animate';

            if (type === 'user') {
                const bubbleColor = 'bg-accent-500 dark:bg-dark-900';
                messageDiv.innerHTML = `
                    <div class="flex justify-end">
                        <div class="max-w-[80%] ${bubbleColor} text-white rounded-2xl rounded-br-md px-4 py-3">
                            <p class="text-sm whitespace-pre-wrap">${escapeHtml(content)}</p>
                        </div>
                    </div>
                `;
            } else if (type === 'error') {
                messageDiv.innerHTML = `
                    <div class="flex gap-3">
                        <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 dark:text-red-400"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm text-red-600 dark:text-red-400">${escapeHtml(content)}</div>
                        </div>
                    </div>
                `;
            }

            chatContent.appendChild(messageDiv);
            lucide.createIcons();
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function handleResize() {
            if (window.innerWidth < 768) {
                referencePanel.classList.add('collapsed');
                panelVisible = false;
            } else if (!panelVisible && window.innerWidth >= 768) {
                referencePanel.classList.remove('collapsed');
                panelVisible = true;
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize();
    </script>
</body>
</html>
