<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Session;

class AuthController
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900; // 15 perc

    public function loginForm(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        view('auth/login');
    }

    public function login(): void
    {
        Middleware::verifyCsrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // reCAPTCHA ellenőrzés
        $recaptchaSecret = getenv('RECAPTCHA_SECRET');
        if ($recaptchaSecret) {
            $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
            if (empty($recaptchaResponse) || !$this->verifyRecaptcha($recaptchaSecret, $recaptchaResponse)) {
                save_old_input();
                set_flash('error', 'Kérjük igazolja, hogy nem robot.');
                redirect('/login');
            }
        }

        // Rate limiting: IP alapú
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $attempts = $_SESSION['login_attempts'][$ip] ?? ['count' => 0, 'last' => 0];

        if ($attempts['count'] >= self::MAX_ATTEMPTS) {
            $elapsed = time() - $attempts['last'];
            if ($elapsed < self::LOCKOUT_SECONDS) {
                $remaining = self::LOCKOUT_SECONDS - $elapsed;
                $minutes = floor($remaining / 60);
                $seconds = $remaining % 60;
                $timeText = $minutes > 0 ? "{$minutes} perc {$seconds} másodperc" : "{$seconds} másodperc";
                set_flash('error', "Túl sok sikertelen próbálkozás (5/5). Próbáld újra {$timeText} múlva.");
                redirect('/login');
            }
            $attempts = ['count' => 0, 'last' => 0];
        }

        $errors = [];
        if (empty($email)) $errors[] = 'Az email cím megadása kötelező.';
        if (empty($password)) $errors[] = 'A jelszó megadása kötelező.';

        if (!empty($errors)) {
            save_old_input();
            set_flash('error', implode(' ', $errors));
            redirect('/login');
        }

        if (Auth::attempt($email, $password, $remember)) {
            unset($_SESSION['login_attempts'][$ip]);
            clear_old_input();

            // Mobilon PIN beállítás felajánlása
            $isMobile = preg_match('/Mobi|Android|iPhone|iPad/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
            if ($isMobile) {
                $user = Auth::user();
                $userData = json_encode(['name' => $user['name'], 'id' => $user['id']]);
                redirect('/login?setup_pin=1&user_data=' . urlencode($userData));
            }

            set_flash('success', 'Sikeres bejelentkezés!');
            redirect('/');
        }

        $attempts['count']++;
        $attempts['last'] = time();
        $_SESSION['login_attempts'][$ip] = $attempts;

        $remaining = self::MAX_ATTEMPTS - $attempts['count'];
        save_old_input();
        if ($remaining > 0) {
            set_flash('error', "Hibás email cím vagy jelszó. Még {$remaining} próbálkozás.");
        } else {
            set_flash('error', 'Hibás email cím vagy jelszó. A fiók zárolva lett 15 percre.');
        }
        redirect('/login');
    }

    public function logout(): void
    {
        Middleware::verifyCsrf();
        Auth::logout();
        redirect('/login');
    }

    private function verifyRecaptcha(string $secret, string $response): bool
    {
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret'   => $secret,
                'response' => $response,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) return false;
        $data = json_decode($result, true);
        return ($data['success'] ?? false) === true;
    }
}
