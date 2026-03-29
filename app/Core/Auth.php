<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    private static ?array $cachedUser = null;

    /**
     * Bejelentkezési kísérlet
     */
    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = User::findByEmail($email);

        if (!$user || !$user['is_active']) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Session beállítás
        session_regenerate_id(true);
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);
        Session::set('_last_regeneration', time());

        // Remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            User::updateRememberToken($user['id'], $token);

            setcookie('remember_token', $token, [
                'expires'  => time() + (30 * 24 * 60 * 60), // 30 nap
                'path'     => '/',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly'  => true,
                'samesite'  => 'Strict',
            ]);
            setcookie('remember_user', (string)$user['id'], [
                'expires'  => time() + (30 * 24 * 60 * 60),
                'path'     => '/',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly'  => true,
                'samesite'  => 'Strict',
            ]);
        }

        self::$cachedUser = null;
        return true;
    }

    /**
     * Remember me cookie-ból bejelentkezés
     */
    public static function loginFromCookie(): bool
    {
        if (self::check()) {
            return true;
        }

        $token = $_COOKIE['remember_token'] ?? null;
        $userId = $_COOKIE['remember_user'] ?? null;

        if (!$token || !$userId) {
            return false;
        }

        $user = User::find((int)$userId);
        if (!$user || !$user['is_active'] || !$user['remember_token']) {
            return false;
        }

        if (!hash_equals($user['remember_token'], $token)) {
            return false;
        }

        // Bejelentkeztetés
        session_regenerate_id(true);
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);
        Session::set('_last_regeneration', time());

        // Token rotáció
        $newToken = bin2hex(random_bytes(32));
        User::updateRememberToken($user['id'], $newToken);
        setcookie('remember_token', $newToken, [
            'expires'  => time() + (30 * 24 * 60 * 60),
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly'  => true,
            'samesite'  => 'Strict',
        ]);

        self::$cachedUser = null;
        return true;
    }

    /**
     * Be van-e jelentkezve
     */
    public static function check(): bool
    {
        return Session::has('user_id');
    }

    /**
     * Bejelentkezett felhasználó adatai
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        if (self::$cachedUser === null) {
            self::$cachedUser = User::find(Session::get('user_id'));
        }

        return self::$cachedUser;
    }

    /**
     * Felhasználó ID-je
     */
    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    /**
     * Felhasználó szerepköre
     */
    public static function role(): ?string
    {
        return Session::get('user_role');
    }

    /**
     * Tulajdonos-e
     */
    public static function isOwner(): bool
    {
        return self::role() === 'tulajdonos';
    }

    /**
     * Könyvelő-e
     */
    public static function isAccountant(): bool
    {
        return self::role() === 'konyvelo';
    }

    /**
     * Bolt fiók-e
     */
    public static function isStore(): bool
    {
        return self::role() === 'bolt';
    }

    /**
     * Bolt fiók store_id-je
     */
    public static function storeId(): ?int
    {
        $user = self::user();
        return $user['store_id'] ?? null;
    }

    /**
     * Kijelentkezés
     */
    public static function logout(): void
    {
        $userId = self::id();

        if ($userId) {
            User::updateRememberToken($userId, null);
        }

        // Remember me cookie törlése
        setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
        setcookie('remember_user', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);

        Session::destroy();
        self::$cachedUser = null;
    }
}
