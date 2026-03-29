<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Session;

class AuthController
{
    // Max próbálkozás és zárolási idő
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 300; // 5 perc

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

        // Rate limiting: IP alapú
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $attempts = $_SESSION['login_attempts'][$ip] ?? ['count' => 0, 'last' => 0];

        // Zárolás ellenőrzés
        if ($attempts['count'] >= self::MAX_ATTEMPTS) {
            $elapsed = time() - $attempts['last'];
            if ($elapsed < self::LOCKOUT_SECONDS) {
                $remaining = self::LOCKOUT_SECONDS - $elapsed;
                $minutes = ceil($remaining / 60);
                set_flash('error', "Túl sok sikertelen próbálkozás. Próbáld újra {$minutes} perc múlva.");
                redirect('/login');
            }
            // Zárolás lejárt, nullázzuk
            $attempts = ['count' => 0, 'last' => 0];
        }

        // Validáció
        $errors = [];
        if (empty($email)) {
            $errors[] = 'Az email cím megadása kötelező.';
        }
        if (empty($password)) {
            $errors[] = 'A jelszó megadása kötelező.';
        }

        if (!empty($errors)) {
            save_old_input();
            set_flash('error', implode(' ', $errors));
            redirect('/login');
        }

        if (Auth::attempt($email, $password, $remember)) {
            // Sikeres belépés: számláló nullázás
            unset($_SESSION['login_attempts'][$ip]);
            clear_old_input();
            set_flash('success', 'Sikeres bejelentkezés!');
            redirect('/');
        }

        // Sikertelen: számláló növelés
        $attempts['count']++;
        $attempts['last'] = time();
        $_SESSION['login_attempts'][$ip] = $attempts;

        save_old_input();
        set_flash('error', 'Hibás email cím vagy jelszó.');
        redirect('/login');
    }

    public function logout(): void
    {
        Middleware::verifyCsrf();
        Auth::logout();
        redirect('/login');
    }
}
