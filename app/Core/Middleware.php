<?php

namespace App\Core;

class Middleware
{
    /**
     * Bejelentkezés megkövetelése
     */
    public static function auth(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
    }

    /**
     * Szerepkör megkövetelése
     */
    public static function role(string $role): void
    {
        self::auth();

        if (Auth::role() !== $role) {
            set_flash('error', 'Nincs jogosultságod ehhez a művelethez.');
            redirect('/');
        }
    }

    /**
     * Tulajdonos megkövetelése
     */
    public static function owner(): void
    {
        self::role('tulajdonos');
    }

    /**
     * Tab jogosultság ellenőrzése
     * Tulajdonos: ha nincs jogosultság beállítva → teljes hozzáférés
     *             ha van beállítva → azok érvényesülnek
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

        if ($permission === 'edit' && !$perm['can_edit']) {
            set_flash('error', 'Nincs szerkesztési jogosultsága ehhez a funkcióhoz.');
            redirect('/');
        }
    }

    /**
     * CSRF token ellenőrzése POST kéréseknél
     */
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
