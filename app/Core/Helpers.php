<?php

/**
 * XSS-biztos szöveg kimenet
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Átirányítás (csak belső útvonalakra)
 */
function redirect(string $path): never
{
    // Csak relatív útvonal engedélyezett (open redirect védelem)
    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }
    $base = rtrim((require __DIR__ . '/../../config/app.php')['base_url'], '/');
    header("Location: {$base}{$path}");
    exit;
}

/**
 * Visszairányítás az előző oldalra (szűrők megőrzése)
 * Ha nincs Referer vagy külső URL, a megadott fallback-re irányít.
 */
function redirect_back(string $fallback = '/'): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $base = rtrim((require __DIR__ . '/../../config/app.php')['base_url'], '/');

    // Csak saját domainről fogadunk el Referer-t (open redirect védelem)
    if ($referer && str_contains($referer, parse_url($base, PHP_URL_HOST) ?? '')) {
        header("Location: {$referer}");
        exit;
    }
    redirect($fallback);
}

/**
 * CSRF hidden input mező generálás
 */
function csrf_field(): string
{
    $token = App\Core\Session::csrfToken();
    return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
}

/**
 * Régi form input visszatöltése
 */
function old(string $key, string $default = ''): string
{
    return $_SESSION['_old_input'][$key] ?? $default;
}

/**
 * Flash üzenet lekérése és törlése
 */
function flash(string $key): mixed
{
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

/**
 * Flash üzenet beállítása
 */
function set_flash(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

/**
 * Régi input mentése (POST redirect után) — jelszó nélkül!
 */
function save_old_input(): void
{
    $safe = $_POST;
    // Soha ne mentsünk jelszót
    unset($safe['password'], $safe['password_confirmation'], $safe['_csrf']);
    $_SESSION['_old_input'] = $safe;
}

/**
 * Régi input törlése
 */
function clear_old_input(): void
{
    unset($_SESSION['_old_input']);
}

/**
 * View renderelés (biztonságos, korlátozott scope)
 */
function view(string $path, array $_viewData = []): void
{
    // Path traversal védelem
    $_safePath = str_replace(['..', "\0"], '', $path);
    $_viewFile = __DIR__ . '/../Views/' . $_safePath . '.php';

    if (!file_exists($_viewFile)) {
        http_response_code(404);
        die('View not found.');
    }

    // Változók kicsomagolása a view számára
    // Az _ prefixes változók nem ütköznek a kicsomagoltakkal
    extract($_viewData);
    require $_viewFile;
}

/**
 * Base URL generálás
 */
function base_url(string $path = ''): string
{
    $base = rtrim((require __DIR__ . '/../../config/app.php')['base_url'], '/');
    return $base . $path;
}

/**
 * Aktuális URL ellenőrzés (sidebar active state)
 */
function is_current(string $path): bool
{
    $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return $current === $path || str_starts_with($current, $path . '/');
}

/**
 * Szám formázás magyar stílusban
 */
function format_number(float|int|null $number): string
{
    return number_format($number ?? 0, 0, ',', ' ');
}

/**
 * Pénzösszeg formázás
 */
function format_money(float|int|null $amount): string
{
    return number_format($amount ?? 0, 0, ',', ' ') . ' Ft';
}
