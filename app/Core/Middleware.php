<?php

namespace App\Core;

class Middleware
{
    public static function auth(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
    }

    public static function role(string $role): void
    {
        self::auth();
        if (Auth::role() !== $role) {
            set_flash('error', 'Nincs jogosultságod ehhez a művelethez.');
            redirect('/');
        }
    }

    public static function owner(): void
    {
        self::role('tulajdonos');
    }

    /**
     * Tab jogosultság ellenőrzése
     * permission: 'view' = megtekintés, 'create' = új rögzítés, 'edit' = módosítás/törlés
     */
    public static function tabPermission(string $tabSlug, string $permission = 'view'): void
    {
        self::auth();

        // Tulajdonos: ha nincs jogosultsága beállítva, mindenhez hozzáfér
        if (Auth::isOwner() && !\App\Models\TabPermission::hasAnyPermissions(Auth::id())) {
            return;
        }

        $user = Auth::user();
        $perm = \App\Models\TabPermission::getPermission($user['id'], $tabSlug);

        if (!$perm) {
            set_flash('error', 'Ez a funkció nem elérhető az Ön fiókjával.');
            redirect('/');
        }

        if ($permission === 'view' && !$perm['can_view']) {
            set_flash('error', 'Ez a funkció nem elérhető az Ön fiókjával.');
            redirect('/');
        }

        if ($permission === 'create' && !$perm['can_create']) {
            set_flash('error', 'Nincs rögzítési jogosultsága ehhez a funkcióhoz.');
            redirect('/');
        }

        if ($permission === 'edit' && !$perm['can_edit']) {
            set_flash('error', 'Nincs módosítási/törlési jogosultsága ehhez a funkcióhoz.');
            redirect('/');
        }
    }

    public static function verifyCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Session::verifyCsrf($token)) {
                http_response_code(403);
                die('Érvénytelen CSRF token. Kérjük, frissítse az oldalt és próbálja újra.');
            }
        }
    }
}
