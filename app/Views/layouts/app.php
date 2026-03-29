<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($data['pageTitle'] ?? 'Elite Fashion') ?> - Elite Fashion Ügyvitel</title>
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
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 mt-16">
                <!-- Flash messages -->
                <?php include __DIR__ . '/../partials/flash.php'; ?>

                <!-- Tartalom -->
                <?php if (isset($content)) { view($content, ['data' => $data ?? []]); } ?>
            </main>
        </div>
    </div>

    <!-- Mobile sidebar overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <script src="<?= base_url('/assets/js/app.js') ?>"></script>
</body>
</html>
