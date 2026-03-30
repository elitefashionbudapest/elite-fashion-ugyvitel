<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= e($data['pageTitle'] ?? 'Elite Fashion') ?> - Elite Fashion Ügyvitel</title>

    <!-- PWA -->
    <link rel="manifest" href="<?= base_url('/manifest.json') ?>">
    <meta name="theme-color" content="#0b0f0e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Elite Fashion">
    <link rel="apple-touch-icon" href="<?= base_url('/assets/icons/icon-192.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#506300',
                        'primary-container': '#d4fa4f',
                        'on-primary': '#e2ff80',
                        'on-primary-container': '#4b5e00',
                        'on-primary-fixed': '#3b4a00',
                        'on-primary-fixed-variant': '#546800',
                        secondary: '#5a5c5e',
                        'secondary-container': '#e2e2e5',
                        'on-secondary-container': '#505254',
                        tertiary: '#605e20',
                        'tertiary-container': '#fefaab',
                        'on-tertiary-container': '#626021',
                        error: '#b02500',
                        'error-container': '#f95630',
                        surface: '#f4f7f5',
                        'surface-dim': '#d0d6d3',
                        'surface-bright': '#f4f7f5',
                        'surface-container-lowest': '#ffffff',
                        'surface-container-low': '#eef2ef',
                        'surface-container': '#e5e9e7',
                        'surface-container-high': '#dfe4e1',
                        'surface-container-highest': '#d8dedc',
                        'surface-variant': '#d8dedc',
                        'on-surface': '#2b2f2e',
                        'on-surface-variant': '#585c5b',
                        'inverse-surface': '#0b0f0e',
                        'inverse-primary': '#d7fd52',
                        outline: '#747876',
                        'outline-variant': '#aaaeac',
                        sidebar: '#0b0f0e',
                        accent: '#D9FF54',
                    },
                    fontFamily: {
                        heading: ['Manrope', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                    borderRadius: { DEFAULT: '1rem', lg: '2rem', xl: '3rem', full: '9999px' },
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= base_url('/assets/css/app.css') ?>">
    <meta name="csrf-token" content="<?= e(App\Core\Session::csrfToken()) ?>">
</head>
<body class="bg-surface text-gray-900 font-body min-h-screen overflow-x-hidden">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <!-- Main content area -->
        <div class="flex-1 flex flex-col ml-0 lg:ml-64 min-h-screen">
            <!-- Header -->
            <?php include __DIR__ . '/../partials/header.php'; ?>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-2 sm:p-4 md:p-6 mt-16 overflow-x-hidden">
                <!-- Flash messages -->
                <?php include __DIR__ . '/../partials/flash.php'; ?>

                <!-- Tartalom -->
                <?php if (isset($content)) { view($content, ['data' => $data ?? []]); } ?>
            </main>
        </div>
    </div>

    <!-- Mobile sidebar overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Nap zárása modal -->
    <div id="dayclose-overlay" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4" onclick="if(event.target===this)closeDayCloseModal()">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 sm:p-8 max-h-[90vh] overflow-y-auto" id="dayclose-content">
            <div class="text-center py-8 text-gray-400"><i class="fa-solid fa-spinner fa-spin text-2xl"></i></div>
        </div>
    </div>

    <script src="<?= base_url('/assets/js/app.js') ?>"></script>

    <!-- PWA: Service Worker + Install Prompt -->
    <script>
    // Service Worker regisztráció
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?= base_url('/sw.js') ?>')
            .then(reg => { console.log('SW registered'); })
            .catch(err => { console.log('SW error:', err); });
    }

    // Install prompt (A2HS)
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        // Megmutatjuk az install gombot
        const btn = document.getElementById('pwa-install-btn');
        if (btn) btn.classList.remove('hidden');
    });

    function installPWA() {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(choice => {
            deferredPrompt = null;
            const btn = document.getElementById('pwa-install-btn');
            if (btn) btn.classList.add('hidden');
        });
    }

    // Értesítés engedély kérése
    function requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(perm => {
                if (perm === 'granted') {
                    console.log('Notification permission granted');
                }
            });
        }
    }

    // Chat hang értesítés (böngészőben)
    let lastKnownMsgCount = 0;
    function checkNewMessages() {
        if (document.hidden) return; // Csak ha aktív a tab
        // A chat polling már kezeli — itt hang + notification
    }

    // Browser notification új üzenetről (ha háttérben van az app)
    function showChatNotification(senderName, message) {
        if ('Notification' in window && Notification.permission === 'granted' && document.hidden) {
            new Notification('Elite Fashion — ' + senderName, {
                body: message,
                icon: '<?= base_url('/assets/icons/icon-192.png') ?>',
                tag: 'chat-msg',
                vibrate: [200, 100, 200],
            });
        }
    }

    // Értesítés engedélyt kérünk bejelentkezés után
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(requestNotificationPermission, 3000);
    });
    </script>

    <!-- PWA Install gomb (sidebar alján) -->
    <div id="pwa-install-btn" class="hidden fixed bottom-4 left-4 z-50 lg:bottom-auto lg:top-auto">
        <button onclick="installPWA()" class="flex items-center gap-2 px-4 py-2.5 bg-accent text-sidebar font-bold text-xs rounded-full shadow-lg hover:shadow-xl transition-all">
            <i class="fa-solid fa-download"></i> App telepítése
        </button>
    </div>
</body>
</html>
