<?php
/**
 * Egyenleg debug script — futtasd a szerveren: php debug-balance.php
 * VAGY hívd meg böngészőből: /ugyvitel/debug-balance.php
 * Töröld utána!
 */
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

$db = App\Core\Database::getInstance();

// OTP bank ID keresése
$stmt = $db->prepare("SELECT id, name, opening_balance FROM banks WHERE name LIKE '%OTP%' LIMIT 1");
$stmt->execute();
$bank = $stmt->fetch();

if (!$bank) { echo "OTP bank nem található!\n"; exit; }

$bankId = $bank['id'];
echo "=== EGYENLEG DEBUG: {$bank['name']} (ID: {$bankId}) ===\n";
echo "Nyitó egyenleg: " . number_format($bank['opening_balance'], 2, ',', ' ') . " Ft\n\n";

$balance = (float)$bank['opening_balance'];

// Minden komponens kiszámítása
$components = [];

// + bank_kifizetes (financial_records)
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_records WHERE bank_id = :id AND purpose = 'bank_kifizetes'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['+ Befizetés boltból (bank_kifizetes)'] = $val;
$balance += $val;

// + kartya_beerkezes
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :id AND type = 'kartya_beerkezes'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['+ Kártyás beérkezés'] = $val;
$balance += $val;

// - befizetes_bankbol
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_records WHERE bank_id = :id AND purpose = 'befizetes_bankbol'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['- Kivét bankból (befizetes_bankbol)'] = $val;
$balance -= $val;

// - szamla_kifizetes (invoices)
$stmt = $db->prepare("SELECT COALESCE(SUM(i.amount), 0) FROM invoices i WHERE i.paid_from_bank_id = :id AND i.is_paid = 1 AND NOT EXISTS (SELECT 1 FROM bank_transactions bt WHERE bt.invoice_id = i.id)");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['- Számla kifizetés (invoices)'] = $val;
$balance -= $val;

// - szolgaltato_levon
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :id AND type = 'szolgaltato_levon'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['- Szolgáltatói levonás'] = $val;
$balance -= $val;

// - hitel_torlesztes
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :id AND type = 'hitel_torlesztes'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['- Hitel törlesztés'] = $val;
$balance -= $val;

// - szamla_kozti OUT
$stmt = $db->prepare("SELECT COALESCE(SUM(source_amount), 0) FROM bank_transactions WHERE bank_id = :id AND type = 'szamla_kozti'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['- Átutalás kimenő'] = $val;
$balance -= $val;

// + szamla_kozti IN
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE target_bank_id = :id AND type = 'szamla_kozti'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['+ Átutalás bejövő'] = $val;
$balance += $val;

// - banki_jutalek
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :id AND type = 'banki_jutalek'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['- Banki jutalék'] = $val;
$balance -= $val;

// - tulajdonosi_fizetes
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :id AND type = 'tulajdonosi_fizetes'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['- Tulajdonosi fizetés'] = $val;
$balance -= $val;

// - ado_kifizetes
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :id AND type = 'ado_kifizetes'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['- Adó kifizetés'] = $val;
$balance -= $val;

// + tagi_kolcson_be
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :id AND type = 'tagi_kolcson_be'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['+ Tagi kölcsön be'] = $val;
$balance += $val;

// - tagi_kolcson_ki
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :id AND type = 'tagi_kolcson_ki'");
$stmt->execute(['id' => $bankId]);
$val = (float)$stmt->fetchColumn();
$components['- Tagi kölcsön ki'] = $val;
$balance -= $val;

echo "=== KOMPONENSEK ===\n";
foreach ($components as $label => $val) {
    if ($val != 0) {
        echo str_pad($label, 45) . number_format($val, 2, ',', ' ') . " Ft\n";
    }
}

echo "\n=== SZÁMÍTOTT EGYENLEG ===\n";
echo number_format($balance, 2, ',', ' ') . " Ft\n";
echo "\nVALÓS BANKI EGYENLEG: 2 811 236 Ft\n";
echo "ELTÉRÉS: " . number_format(2811236 - $balance, 2, ',', ' ') . " Ft\n";

// Részletes tranzakciós lista
echo "\n=== BANK TRANZAKCIÓK RÉSZLETEZVE ===\n";
$stmt = $db->prepare("SELECT type, amount, transaction_date, notes, provider_name FROM bank_transactions WHERE bank_id = :id ORDER BY transaction_date, id");
$stmt->execute(['id' => $bankId]);
$txs = $stmt->fetchAll();
foreach ($txs as $tx) {
    echo sprintf("%-12s %-25s %15s  %s\n",
        $tx['transaction_date'],
        $tx['type'],
        number_format($tx['amount'], 2, ',', ' ') . ' Ft',
        mb_substr($tx['notes'] ?? $tx['provider_name'] ?? '', 0, 50)
    );
}

echo "\n=== SZÁMLÁK amik csökkentik az egyenleget (paid_from_bank, nincs bank_tx) ===\n";
$stmt = $db->prepare(
    "SELECT i.id, i.invoice_number, sp.name as supplier, i.amount, i.invoice_date, i.paid_from_bank_id
     FROM invoices i
     JOIN suppliers sp ON i.supplier_id = sp.id
     WHERE i.paid_from_bank_id = :id AND i.is_paid = 1
     AND NOT EXISTS (SELECT 1 FROM bank_transactions bt WHERE bt.invoice_id = i.id)
     ORDER BY i.invoice_date"
);
$stmt->execute(['id' => $bankId]);
$invoices = $stmt->fetchAll();
$invoiceTotal = 0;
foreach ($invoices as $inv) {
    $invoiceTotal += (float)$inv['amount'];
    echo sprintf("%-12s %-30s %12s  %s\n",
        $inv['invoice_date'],
        e(mb_substr($inv['supplier'], 0, 30)),
        number_format($inv['amount'], 0, ',', ' ') . ' Ft',
        $inv['invoice_number']
    );
}
echo "ÖSSZESEN: " . number_format($invoiceTotal, 0, ',', ' ') . " Ft\n";

echo "\n=== FINANCIAL_RECORDS (bank-related) ===\n";
$stmt = $db->prepare("SELECT purpose, amount, record_date, store_id FROM financial_records WHERE bank_id = :id ORDER BY record_date, id");
$stmt->execute(['id' => $bankId]);
$frs = $stmt->fetchAll();
foreach ($frs as $fr) {
    echo sprintf("%-12s %-25s %15s  store:%s\n",
        $fr['record_date'],
        $fr['purpose'],
        number_format($fr['amount'], 2, ',', ' ') . ' Ft',
        $fr['store_id']
    );
}
