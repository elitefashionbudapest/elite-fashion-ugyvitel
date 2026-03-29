<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>PHP Test</h2>";
echo "PHP verzió: " . PHP_VERSION . "<br>";

// Teszt 1: .env olvasás
$envFile = __DIR__ . '/../.env';
echo ".env fájl létezik: " . (file_exists($envFile) ? 'IGEN' : 'NEM') . "<br>";

// Teszt 2: Config betöltés
try {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
    echo "DB_HOST: " . getenv('DB_HOST') . "<br>";
    echo "DB_DATABASE: " . getenv('DB_DATABASE') . "<br>";
} catch (Throwable $e) {
    echo "ENV hiba: " . $e->getMessage() . "<br>";
}

// Teszt 3: DB kapcsolat
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_DATABASE') ?: 'elite_fashion'
    );
    $db = new PDO($dsn, getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: '');
    echo "<span style='color:green;font-weight:bold'>DB kapcsolat OK!</span><br>";
    $cnt = $db->query("SHOW TABLES")->rowCount();
    echo "Táblák száma: " . $cnt . "<br>";
} catch (Throwable $e) {
    echo "<span style='color:red;font-weight:bold'>DB hiba: " . $e->getMessage() . "</span><br>";
}

// Teszt 4: Autoloader
echo "<br><h3>Autoloader teszt:</h3>";
$autoloader = __DIR__ . '/../vendor/autoload.php';
echo "vendor/autoload.php létezik: " . (file_exists($autoloader) ? 'IGEN' : 'NEM') . "<br>";

// Teszt 5: Szükséges PHP kiterjesztések
$exts = ['pdo', 'pdo_mysql', 'mbstring', 'curl', 'zip', 'fileinfo'];
foreach ($exts as $ext) {
    $ok = extension_loaded($ext);
    echo $ext . ": " . ($ok ? "<span style='color:green'>OK</span>" : "<span style='color:red'>HIÁNYZIK</span>") . "<br>";
}
