<?php
/**
 * Teszt: szimulálja a scan endpoint-ot és kiírja mi történik
 * Hívd meg: /ugyvitel/test-scan.php
 * Töröld utána!
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Bootstrap
require __DIR__ . '/../vendor/autoload.php';
if (!class_exists('App\Core\Database')) {
    require __DIR__ . '/../app/Core/Helpers.php';
    spl_autoload_register(function (string $class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../app/';
        if (!str_starts_with($class, $prefix)) return;
        $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) require $file;
    });
}

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        if (!getenv(trim($key))) putenv(trim($key) . '=' . trim($value));
    }
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== SCAN TESZT ===\n\n";

// 1. DB teszt
echo "1. Adatbázis kapcsolat... ";
try {
    $db = App\Core\Database::getInstance();
    echo "OK\n";
} catch (\Throwable $e) {
    echo "HIBA: " . $e->getMessage() . "\n";
    exit;
}

// 2. Products tábla
echo "2. Products tábla... ";
try {
    $stmt = $db->query("SELECT COUNT(*) FROM products");
    echo "OK (" . $stmt->fetchColumn() . " termék)\n";
} catch (\Throwable $e) {
    echo "HIBA: " . $e->getMessage() . "\n";
}

// 3. defect_items tábla + product_name oszlop
echo "3. defect_items product_name oszlop... ";
try {
    $stmt = $db->query("SHOW COLUMNS FROM defect_items LIKE 'product_name'");
    $col = $stmt->fetch();
    echo $col ? "OK\n" : "HIÁNYZIK!\n";
} catch (\Throwable $e) {
    echo "HIBA: " . $e->getMessage() . "\n";
}

echo "4. defect_items product_price oszlop... ";
try {
    $stmt = $db->query("SHOW COLUMNS FROM defect_items LIKE 'product_price'");
    $col = $stmt->fetch();
    echo $col ? "OK\n" : "HIÁNYZIK!\n";
} catch (\Throwable $e) {
    echo "HIBA: " . $e->getMessage() . "\n";
}

// 4. Teszt insert
echo "5. Teszt insert (product_name, product_price)... ";
try {
    $stmt = $db->prepare(
        "INSERT INTO defect_items (store_id, barcode, product_name, product_price, scanned_by, scanned_at)
         VALUES (1, 'TEST_DELETE_ME', 'Teszt termék', 1234.00, 1, NOW())"
    );
    $stmt->execute();
    $testId = (int)$db->lastInsertId();
    echo "OK (id: $testId)\n";

    // Törlés
    $db->prepare("DELETE FROM defect_items WHERE id = :id")->execute(['id' => $testId]);
    echo "   Teszt sor törölve.\n";
} catch (\Throwable $e) {
    echo "HIBA: " . $e->getMessage() . "\n";
}

// 5. Product keresés
echo "6. Product keresés (vonalkód: 0000000039192)... ";
try {
    $product = App\Models\Product::findByBarcode('0000000039192');
    if ($product) {
        echo "OK → " . $product['name'] . " (" . $product['gross_price'] . " Ft)\n";
    } else {
        echo "Nem található (de nem hiba)\n";
    }
} catch (\Throwable $e) {
    echo "HIBA: " . $e->getMessage() . "\n";
}

// 6. AuditLog
echo "7. AuditLog teszt... ";
try {
    App\Core\AuditLog::log('test', 'test', null, null, ['test' => true]);
    echo "OK\n";
    // Törlés
    $db->prepare("DELETE FROM audit_log WHERE table_name = 'test' AND action = 'test'")->execute();
} catch (\Throwable $e) {
    echo "HIBA: " . $e->getMessage() . "\n";
}

// 7. JSON encode teszt
echo "8. JSON encode teszt... ";
$testJson = json_encode([
    'success' => true,
    'item' => [
        'id' => 1,
        'barcode' => '123456',
        'product_name' => 'Teszt árvíztűrő',
        'product_price' => 5990.00,
        'store_name' => 'Teszt bolt',
        'scanned_at' => date('Y-m-d H:i:s'),
    ]
]);
echo $testJson ? "OK\n" : "HIBA: " . json_last_error_msg() . "\n";

echo "\n=== PHP verzió: " . PHP_VERSION . " ===\n";
echo "=== Output buffering: " . (ob_get_level() > 0 ? 'aktív (' . ob_get_level() . ' szint)' : 'inaktív') . " ===\n";
