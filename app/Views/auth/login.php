<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Bejelentkezés - Elite Fashion</title>
    <link rel="manifest" href="<?= base_url('/manifest.json') ?>">
    <meta name="theme-color" content="#0b0f0e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="<?= base_url('/assets/icons/icon-192.png') ?>">
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

        <!-- PIN belépés (mobilon, ha van mentett PIN) -->
        <div id="pin-screen" class="hidden">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <div class="w-16 h-16 rounded-full bg-primary/20 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-lock text-2xl text-sidebar"></i>
                </div>
                <h2 class="font-heading font-bold text-xl text-gray-900 mb-1" id="pin-user-name"></h2>
                <p class="text-sm text-gray-500 mb-6">Add meg a PIN kódod</p>

                <div class="flex justify-center gap-3 mb-6" id="pin-dots">
                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 pin-dot"></div>
                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 pin-dot"></div>
                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 pin-dot"></div>
                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 pin-dot"></div>
                </div>

                <p id="pin-error" class="text-red-500 text-sm mb-4 hidden">Hibás PIN kód!</p>

                <!-- Szám billentyűzet -->
                <div class="grid grid-cols-3 gap-3 max-w-[240px] mx-auto">
                    <button onclick="pinPress(1)" class="pin-key">1</button>
                    <button onclick="pinPress(2)" class="pin-key">2</button>
                    <button onclick="pinPress(3)" class="pin-key">3</button>
                    <button onclick="pinPress(4)" class="pin-key">4</button>
                    <button onclick="pinPress(5)" class="pin-key">5</button>
                    <button onclick="pinPress(6)" class="pin-key">6</button>
                    <button onclick="pinPress(7)" class="pin-key">7</button>
                    <button onclick="pinPress(8)" class="pin-key">8</button>
                    <button onclick="pinPress(9)" class="pin-key">9</button>
                    <button onclick="pinClear()" class="pin-key text-red-500"><i class="fa-solid fa-delete-left"></i></button>
                    <button onclick="pinPress(0)" class="pin-key">0</button>
                    <button onclick="pinSubmit()" class="pin-key bg-primary/20 text-sidebar"><i class="fa-solid fa-check"></i></button>
                </div>

                <button onclick="forgetPin()" class="mt-6 text-xs text-gray-400 hover:text-gray-600">Más fiókkal belépés</button>
            </div>
        </div>

        <!-- PIN beállítás (sikeres login után mobilon) -->
        <div id="pin-setup-screen" class="hidden">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-shield-halved text-2xl text-emerald-600"></i>
                </div>
                <h2 class="font-heading font-bold text-xl text-gray-900 mb-1">PIN kód beállítása</h2>
                <p class="text-sm text-gray-500 mb-6" id="pin-setup-label">Adj meg egy 4 jegyű PIN kódot a gyors belépéshez</p>

                <div class="flex justify-center gap-3 mb-6" id="pin-setup-dots">
                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 pin-dot"></div>
                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 pin-dot"></div>
                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 pin-dot"></div>
                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 pin-dot"></div>
                </div>

                <div class="grid grid-cols-3 gap-3 max-w-[240px] mx-auto">
                    <button onclick="pinSetupPress(1)" class="pin-key">1</button>
                    <button onclick="pinSetupPress(2)" class="pin-key">2</button>
                    <button onclick="pinSetupPress(3)" class="pin-key">3</button>
                    <button onclick="pinSetupPress(4)" class="pin-key">4</button>
                    <button onclick="pinSetupPress(5)" class="pin-key">5</button>
                    <button onclick="pinSetupPress(6)" class="pin-key">6</button>
                    <button onclick="pinSetupPress(7)" class="pin-key">7</button>
                    <button onclick="pinSetupPress(8)" class="pin-key">8</button>
                    <button onclick="pinSetupPress(9)" class="pin-key">9</button>
                    <button onclick="pinSetupClear()" class="pin-key text-red-500"><i class="fa-solid fa-delete-left"></i></button>
                    <button onclick="pinSetupPress(0)" class="pin-key">0</button>
                    <button onclick="pinSetupSubmit()" class="pin-key bg-primary/20 text-sidebar"><i class="fa-solid fa-check"></i></button>
                </div>

                <button onclick="skipPinSetup()" class="mt-6 text-xs text-gray-400 hover:text-gray-600">Kihagyás</button>
            </div>
        </div>

        <!-- Normál login form -->
        <div id="login-screen">
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

                <form method="POST" action="<?= base_url('/login') ?>" class="space-y-5" id="login-form">
                    <?= csrf_field() ?>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email cím</label>
                        <div class="relative">
                            <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                            <input type="email" id="email" name="email" value="<?= e(old('email')) ?>"
                                class="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm"
                                placeholder="pelda@elitedivat.hu" required autofocus>
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Jelszó</label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                            <input type="password" id="password" name="password"
                                class="w-full pl-11 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm"
                                placeholder="••••••••" required>
                            <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fa-solid fa-eye" id="pw-toggle-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" checked
                            class="h-4 w-4 text-sidebar border-gray-300 rounded focus:ring-primary">
                        <label for="remember" class="ml-2 text-sm text-gray-600">Emlékezz rám</label>
                    </div>

                    <?php if ($recaptchaSiteKey): ?>
                    <div class="g-recaptcha" data-sitekey="<?= e($recaptchaSiteKey) ?>"></div>
                    <?php endif; ?>

                    <button type="submit"
                        class="w-full bg-sidebar text-primary py-3 px-4 rounded-xl font-heading font-bold text-sm hover:bg-gray-800 transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-right-to-bracket text-base"></i>
                        Bejelentkezés
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-gray-500 text-xs mt-6">&copy; <?= date('Y') ?> Elite Fashion. Minden jog fenntartva.</p>
    </div>

    <style>
    .pin-key {
        width: 60px; height: 60px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; font-weight: 700; font-family: 'Manrope', sans-serif;
        border-radius: 9999px;
        background: #f4f7f5;
        color: #0b0f0e;
        border: none; cursor: pointer;
        transition: all 0.15s;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    .pin-key:active { transform: scale(0.9); background: #D9FF54; }
    .pin-dot.filled { background: #0b0f0e; border-color: #0b0f0e; }
    .pin-dot.error { border-color: #ef4444; background: #ef4444; }
    </style>

    <script>
    function togglePassword() {
        const pw = document.getElementById('password');
        const icon = document.getElementById('pw-toggle-icon');
        if (pw.type === 'password') { pw.type = 'text'; icon.className = 'fa-solid fa-eye-slash'; }
        else { pw.type = 'password'; icon.className = 'fa-solid fa-eye'; }
    }

    const isMobile = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent) || window.matchMedia('(display-mode: standalone)').matches;
    const baseUrl = '<?= base_url('') ?>';
    let pinCode = '';
    let pinSetupCode = '';
    let pinSetupStep = 1; // 1 = első megadás, 2 = megerősítés
    let pinFirstEntry = '';

    // Ellenőrizzük van-e mentett PIN
    const savedPin = localStorage.getItem('ef_pin');
    const savedUser = localStorage.getItem('ef_pin_user');
    const savedToken = localStorage.getItem('ef_pin_token');

    if (isMobile && savedPin && savedUser && savedToken) {
        // PIN belépés megjelenítése
        document.getElementById('login-screen').classList.add('hidden');
        document.getElementById('pin-screen').classList.remove('hidden');
        document.getElementById('pin-user-name').textContent = JSON.parse(savedUser).name;
    }

    // PIN beállítás megjelenítése sikeres login után (URL paraméter)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('setup_pin') === '1' && isMobile) {
        document.getElementById('login-screen').classList.add('hidden');
        document.getElementById('pin-setup-screen').classList.remove('hidden');
    }

    // === PIN belépés ===
    function pinPress(n) {
        if (pinCode.length >= 4) return;
        pinCode += n;
        updateDots('pin-dots', pinCode.length);
        document.getElementById('pin-error').classList.add('hidden');
        if (pinCode.length === 4) setTimeout(pinSubmit, 200);
    }

    function pinClear() {
        pinCode = pinCode.slice(0, -1);
        updateDots('pin-dots', pinCode.length);
    }

    function pinSubmit() {
        if (pinCode !== savedPin) {
            document.getElementById('pin-error').classList.remove('hidden');
            const dots = document.querySelectorAll('#pin-dots .pin-dot');
            dots.forEach(d => { d.classList.add('error'); });
            setTimeout(() => {
                pinCode = '';
                updateDots('pin-dots', 0);
                dots.forEach(d => d.classList.remove('error'));
            }, 500);
            return;
        }
        // PIN helyes — beléptetés remember token-nel
        window.location.href = baseUrl + '/';
    }

    function forgetPin() {
        localStorage.removeItem('ef_pin');
        localStorage.removeItem('ef_pin_user');
        localStorage.removeItem('ef_pin_token');
        document.getElementById('pin-screen').classList.add('hidden');
        document.getElementById('login-screen').classList.remove('hidden');
    }

    // === PIN beállítás ===
    function pinSetupPress(n) {
        if (pinSetupCode.length >= 4) return;
        pinSetupCode += n;
        updateDots('pin-setup-dots', pinSetupCode.length);
        if (pinSetupCode.length === 4) setTimeout(pinSetupSubmit, 200);
    }

    function pinSetupClear() {
        pinSetupCode = pinSetupCode.slice(0, -1);
        updateDots('pin-setup-dots', pinSetupCode.length);
    }

    function pinSetupSubmit() {
        if (pinSetupStep === 1) {
            pinFirstEntry = pinSetupCode;
            pinSetupCode = '';
            pinSetupStep = 2;
            updateDots('pin-setup-dots', 0);
            document.getElementById('pin-setup-label').textContent = 'Írd be újra a megerősítéshez';
        } else {
            if (pinSetupCode === pinFirstEntry) {
                // PIN mentése
                const userData = urlParams.get('user_data');
                localStorage.setItem('ef_pin', pinSetupCode);
                localStorage.setItem('ef_pin_user', userData || '{}');
                localStorage.setItem('ef_pin_token', '1');
                window.location.href = baseUrl + '/';
            } else {
                // Nem egyezik — újra
                pinSetupCode = '';
                pinFirstEntry = '';
                pinSetupStep = 1;
                updateDots('pin-setup-dots', 0);
                document.getElementById('pin-setup-label').textContent = 'Nem egyezik! Adj meg egy 4 jegyű PIN kódot';
            }
        }
    }

    function skipPinSetup() {
        window.location.href = baseUrl + '/';
    }

    function updateDots(containerId, count) {
        const dots = document.querySelectorAll('#' + containerId + ' .pin-dot');
        dots.forEach((d, i) => {
            d.classList.toggle('filled', i < count);
        });
    }

    // Login form — sikeres beküldés után PIN setup-ra irányítás (mobilon)
    if (isMobile && !savedPin) {
        const form = document.getElementById('login-form');
        if (form) {
            form.addEventListener('submit', function() {
                // Remember me automatikusan be legyen pipálva mobilon
                document.getElementById('remember').checked = true;
            });
        }
    }
    </script>
</body>
</html>
