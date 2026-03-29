<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="manifest" href="<?= base_url('/manifest.json') ?>">
    <meta name="theme-color" content="#0b0f0e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="<?= base_url('/assets/icons/icon-192.png') ?>">
    <title>Bejelentkezés - Elite Fashion</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <?php $recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY'); ?>
    <?php if ($recaptchaSiteKey): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#D9FF54',
                        sidebar: '#0b0f0e',
                        surface: '#f4f7f5',
                    },
                    fontFamily: {
                        heading: ['Manrope', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-sidebar min-h-screen font-body flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-heading font-bold text-white">Elite Fashion</h1>
            <p class="text-gray-400 text-sm mt-1">Ügyviteli Rendszer</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="font-heading font-bold text-xl text-gray-900 mb-6">Bejelentkezés</h2>

            <?php if ($error = flash('error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success = flash('success')): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4 text-sm">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= base_url('/login') ?>" class="space-y-5">
                <?= csrf_field() ?>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email cím</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e(old('email')) ?>"
                            class="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm"
                            placeholder="pelda@elitefashion.hu"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <!-- Jelszó -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Jelszó</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm"
                            placeholder="••••••••"
                            required
                        >
                    </div>
                </div>

                <!-- Emlékezz rám -->
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        class="h-4 w-4 text-sidebar border-gray-300 rounded focus:ring-primary"
                    >
                    <label for="remember" class="ml-2 text-sm text-gray-600">Emlékezz rám</label>
                </div>

                <!-- reCAPTCHA -->
                <?php if ($recaptchaSiteKey): ?>
                <div class="g-recaptcha" data-sitekey="<?= e($recaptchaSiteKey) ?>"></div>
                <?php endif; ?>

                <!-- Bejelentkezés gomb -->
                <button
                    type="submit"
                    class="w-full bg-sidebar text-primary py-3 px-4 rounded-xl font-heading font-bold text-sm hover:bg-gray-800 transition-colors flex items-center justify-center gap-2"
                >
                    <i class="fa-solid fa-right-to-bracket text-base"></i>
                    Bejelentkezés
                </button>
            </form>
        </div>

        <p class="text-center text-gray-500 text-xs mt-6">&copy; <?= date('Y') ?> Elite Fashion. Minden jog fenntartva.</p>
    </div>
</body>
</html>
