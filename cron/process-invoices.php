<?php
/**
 * Automatikus számla feldolgozás emailből
 * Futtatás: php cron/process-invoices.php
 * Cron: 0 5 * * * php /home/elitediv/public_html/ugyvitel/cron/process-invoices.php
 */

// Betöltés
require_once __DIR__ . '/../app/Core/Helpers.php';

$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require $autoloader;
} else {
    spl_autoload_register(function (string $class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../app/';
        if (!str_starts_with($class, $prefix)) return;
        $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) require $file;
    });
}

// .env betöltés
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        if (!getenv(trim($key))) putenv(trim($key) . '=' . trim($value));
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Számla email feldolgozás indítása...\n";

$processor = new \App\Services\InvoiceEmailProcessor();
$results = $processor->process();

foreach ($results as $r) {
    $icon = match($r['status'] ?? '') {
        'success' => '✅',
        'error'   => '❌',
        'info'    => 'ℹ️',
        default   => '  ',
    };
    echo "  {$icon} {$r['message']}\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Kész.\n";
