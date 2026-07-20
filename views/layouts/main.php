<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzapp - Plateforme de Quiz Premium en temps réel</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Custom CSS Styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Premium Background Gradients */
        .premium-bg-light {
            background-color: #f8fafc;
            background-image: radial-gradient(at 0% 0%, hsla(253,16%,7%,0) 0, transparent 50%),
                              radial-gradient(at 50% 0%, hsla(225,39%,30%,0.05) 0, transparent 50%),
                              radial-gradient(at 100% 0%, hsla(339,49%,30%,0.05) 0, transparent 50%);
        }
        
        .premium-bg-dark {
            background-color: #0b0f19;
            background-image: radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%),
                              radial-gradient(at 50% 0%, hsla(263,45%,15%,0.4) 0, transparent 50%),
                              radial-gradient(at 100% 0%, hsla(225,39%,10%,1) 0, transparent 50%);
        }

        /* Glassmorphism Classes */
        .glass-card {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        
        .dark .glass-card {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #334155;
        }
        
        /* Floating micro-animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: float 4s ease-in-out infinite;
        }

        /* High Contrast Dark Mode Form & Text Overrides */
        input, select, textarea {
            color: #1e293b !important; /* slate-800 */
        }
        .dark input, .dark select, .dark textarea {
            color: #f8fafc !important; /* slate-50 */
        }
        .dark option {
            background-color: #0f172a !important; /* slate-900 */
            color: #f8fafc !important; /* slate-50 */
        }
        .dark ::placeholder {
            color: #94a3b8 !important; /* slate-400 */
            opacity: 1 !important;
        }
        .dark .text-slate-500 {
            color: #cbd5e1 !important; /* slate-300 */
        }
        .dark .text-slate-400 {
            color: #cbd5e1 !important; /* slate-300 */
        }
        .dark .text-slate-600 {
            color: #e2e8f0 !important; /* slate-200 */
        }
        .dark .text-slate-700 {
            color: #f1f5f9 !important; /* slate-100 */
        }
        /* Ensure disabled answer inputs have proper opacity but stay readable */
        .dark .opacity-50 {
            opacity: 0.65 !important;
        }
    </style>

    <!-- Theme Initialization Script -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="premium-bg-light dark:premium-bg-dark text-slate-800 dark:text-slate-100 min-h-screen flex flex-col font-sans transition-colors duration-300 overflow-x-hidden">

    <!-- Header Navigation -->
    <header class="sticky top-0 z-40 w-full glass-card transition-all duration-300" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Mobile Hamburger Menu Button -->
                <div class="flex items-center md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-sm transition-all focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" x-show="!mobileMenuOpen"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" x-show="mobileMenuOpen" x-cloak></path>
                        </svg>
                    </button>
                </div>

                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <span class="text-2xl font-extrabold bg-gradient-to-r from-violet-500 via-purple-500 to-amber-500 bg-clip-text text-transparent tracking-tight font-black">QUIZZAPP</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="/" class="text-sm font-medium hover:text-brand-500 transition-colors">Accueil</a>
                    <a href="/#categories-list" class="text-sm font-medium hover:text-brand-500 transition-colors">Quiz</a>
                    <a href="/duel" class="text-sm font-medium hover:text-brand-500 transition-colors">Duel Privé</a>
                    <?php if (isset($_SESSION['user']) && (int)$_SESSION['user']['role_id'] === 1): ?>
                        <a href="/admin" class="text-sm font-semibold text-amber-500 dark:text-amber-400 hover:text-amber-600 transition-colors">Administration</a>
                    <?php endif; ?>
                </nav>

                <!-- Action Controls & Profile -->
                <div class="flex items-center space-x-4">
                    <!-- Light / Dark Mode Toggle -->
                    <button id="theme-toggle" class="p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-sm transition-all focus:outline-none">
                        <!-- Dark Icon -->
                        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                        <!-- Light Icon -->
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.46 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                    </button>

                    <?php if (isset($_SESSION['user'])): ?>
                        <!-- Logged User -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                                <img class="w-8 h-8 rounded-full border border-violet-500" 
                                     src="<?php echo $_SESSION['user']['avatar_url'] ?: 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($_SESSION['user']['username']); ?>" 
                                     alt="Avatar">
                                <span class="hidden sm:inline-block text-sm font-semibold hover:text-brand-500 transition-colors"><?php echo \App\Core\View::escape($_SESSION['user']['username']); ?></span>
                            </button>
                            <!-- Dropdown Menu -->
                            <div x-show="open" @click.outside="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-slate-800 ring-1 ring-black ring-opacity-5 focus:outline-none transition-all duration-200">
                                <a href="/dashboard" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">Mon Tableau de Bord</a>
                                <a href="/settings" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">Paramètres</a>
                                <div class="border-t border-slate-200 dark:border-slate-700"></div>
                                <form id="logout-form" action="/logout" method="POST" class="hidden">
                                    <input type="hidden" name="csrf_token" value="<?php echo \App\Core\Session::csrfToken(); ?>">
                                </form>
                                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block px-4 py-2 text-sm text-red-600 hover:bg-slate-100 dark:hover:bg-slate-700">Déconnexion</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Auth buttons -->
                        <div class="flex items-center space-x-2">
                            <a href="/login" class="px-3 py-1.5 text-sm font-medium hover:text-brand-500 transition-colors">Connexion</a>
                            <a href="/register" class="px-4 py-1.5 text-sm font-semibold text-white bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">S'inscrire</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Mobile Dropdown Menu -->
        <div x-show="mobileMenuOpen" x-cloak @click.away="mobileMenuOpen = false" 
             class="md:hidden border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-4 py-4 space-y-2 transition-all duration-300">
            <a href="/" @click="mobileMenuOpen = false" class="block text-sm font-medium hover:text-brand-500 py-2 transition-colors">Accueil</a>
            <a href="/#categories-list" @click="mobileMenuOpen = false" class="block text-sm font-medium hover:text-brand-500 py-2 transition-colors">Quiz</a>
            <a href="/duel" @click="mobileMenuOpen = false" class="block text-sm font-medium hover:text-brand-500 py-2 transition-colors">Duel Privé</a>
            <?php if (isset($_SESSION['user']) && (int)$_SESSION['user']['role_id'] === 1): ?>
                <a href="/admin" @click="mobileMenuOpen = false" class="block text-sm font-semibold text-amber-500 py-2 transition-colors">Administration</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Notifications Alerts -->
    <main class="flex-grow w-full max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8">
        <?php if ($error = \App\Core\Session::getFlash('error')): ?>
            <div class="mb-6 p-4 rounded-xl border border-red-500/20 bg-red-500/10 text-red-500 text-sm flex items-center space-x-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                <span><?php echo \App\Core\View::escape($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success = \App\Core\Session::getFlash('success')): ?>
            <div class="mb-6 p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-500 text-sm flex items-center space-x-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span><?php echo \App\Core\View::escape($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Content Area -->
        <?php echo $content; ?>
    </main>

    <!-- Footer -->
    <footer class="w-full py-6 mt-12 glass-card border-t border-slate-200 dark:border-slate-800 text-center text-sm text-slate-500 dark:text-slate-400">
        <div class="max-w-7xl mx-auto px-4">
            <p>&copy; 2026 Quizzapp. Tous droits réservés. Construit avec neutralité éditoriale stricte.</p>
        </div>
    </footer>

    <!-- Dark Mode Theme Toggler Script -->
    <script>
        var themeToggleBtn = document.getElementById('theme-toggle');
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        // Show icons accordingly
        if (document.documentElement.classList.contains('dark')) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }
            } else {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
            }
        });
    </script>
</body>
</html>
