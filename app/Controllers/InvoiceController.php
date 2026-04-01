<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\{Invoice, Supplier, Store, Bank};

class InvoiceController
{
    public function index(): void
    {
        Middleware::tabPermission('szamlak', 'view');

        $storeId = Auth::isStore() ? Auth::storeId() : ($_GET['store_id'] ?? null);
        $supplierId = $_GET['supplier_id'] ?? null;
        $isPaid = $_GET['is_paid'] ?? null;

        $invoices = Invoice::all(
            $storeId ? (int)$storeId : null,
            $supplierId ? (int)$supplierId : null,
            $isPaid
        );

        $stores = Auth::isOwner() ? Store::all() : [];
        $suppliers = Supplier::all();
        $banks = Bank::all();

        view('layouts/app', [
            'content' => 'invoices/index',
            'data' => [
                'pageTitle'  => 'Bejövő számlák',
                'activeTab'  => 'szamlak',
                'invoices'   => $invoices,
                'stores'     => $stores,
                'suppliers'  => $suppliers,
                'banks'      => $banks,
                'filters'    => ['store_id' => $storeId, 'supplier_id' => $supplierId, 'is_paid' => $isPaid],
            ]
        ]);
    }

    public function create(): void
    {
        Middleware::tabPermission('szamlak', 'create');

        $stores = Auth::isOwner() ? Store::all() : [];

        view('layouts/app', [
            'content' => 'invoices/form',
            'data' => [
                'pageTitle' => 'Számla rögzítés',
                'activeTab' => 'szamlak',
                'stores'    => $stores,
            ]
        ]);
    }

    public function store(): void
    {
        Middleware::tabPermission('szamlak', 'create');
        Middleware::verifyCsrf();

        $storeId = Auth::isStore() ? Auth::storeId() : (!empty($_POST['store_id']) ? (int)$_POST['store_id'] : null);
        $supplierName = trim($_POST['supplier_name'] ?? '');
        $invoiceNumber = trim($_POST['invoice_number'] ?? '');
        $netAmount = (float)($_POST['net_amount'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $currency = $_POST['currency'] ?? 'HUF';
        $invoiceDate = $_POST['invoice_date'] ?? date('Y-m-d');
        $dueDate = $_POST['due_date'] ?? '';
        $paymentMethod = $_POST['payment_method'] ?? 'atutalas';
        $notes = trim($_POST['notes'] ?? '');

        if (empty($supplierName) || empty($invoiceNumber) || $amount <= 0) {
            save_old_input();
            set_flash('error', 'A beszállító neve, számlaszám és összeg kötelező.');
            redirect('/invoices/create');
        }

        // Ha nettó nincs megadva, legyen egyenlő a bruttóval (0% ÁFA)
        if ($netAmount <= 0) $netAmount = $amount;

        // Beszállító keresése vagy létrehozása
        $supplierId = Supplier::findOrCreate($supplierName);

        $id = Invoice::create([
            'store_id'       => $storeId,
            'supplier_id'    => $supplierId,
            'invoice_number' => $invoiceNumber,
            'net_amount'     => $netAmount,
            'amount'         => $amount,
            'currency'       => $currency,
            'invoice_date'   => $invoiceDate,
            'due_date'       => $dueDate,
            'payment_method' => $paymentMethod,
            'notes'          => $notes,
            'recorded_by'    => Auth::id(),
        ]);

        // Képfeltöltés
        if (!empty($_FILES['invoice_image']['tmp_name'])) {
            $this->handleImageUpload($id, $invoiceDate);
        }

        AuditLog::log('create', 'invoices', $id, null, ['supplier' => $supplierName, 'amount' => $amount]);
        set_flash('success', 'Számla sikeresen rögzítve.');
        redirect('/invoices');
    }

    private function handleImageUpload(int $invoiceId, string $date): void
    {
        $file = $_FILES['invoice_image'];
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 10 * 1024 * 1024 || $file['size'] === 0) return;

        // MIME típus ellenőrzés (nem csak kiterjesztés!)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        if (!isset($allowedMimes[$mime])) return;
        $ext = $allowedMimes[$mime];

        // Dátum validáció
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        // Havi mappa: uploads/invoices/2026-03/
        $monthDir = date('Y-m', strtotime($date));
        $uploadDir = __DIR__ . '/../../public/uploads/invoices/' . $monthDir;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Véletlenszerű fájlnév (nem kiszámítható)
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            Invoice::updateImage($invoiceId, '/uploads/invoices/' . $monthDir . '/' . $filename);
        }
    }

    public function markPaid(string $id): void
    {
        Middleware::tabPermission('szamlak', 'edit');
        Middleware::verifyCsrf();

        $bankId = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
        Invoice::markPaid((int)$id, $bankId);
        AuditLog::log('update', 'invoices', (int)$id, null, ['is_paid' => 1, 'bank_id' => $bankId]);
        set_flash('success', 'Számla fizetve.');
        redirect('/invoices');
    }

    public function markUnpaid(string $id): void
    {
        Middleware::tabPermission('szamlak', 'edit');
        Middleware::verifyCsrf();

        Invoice::markUnpaid((int)$id);
        AuditLog::log('update', 'invoices', (int)$id, null, ['is_paid' => 0]);
        redirect('/invoices');
    }

    public function destroy(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $invoice = Invoice::find((int)$id);
        if ($invoice) {
            Invoice::delete((int)$id);
            AuditLog::log('delete', 'invoices', (int)$id, $invoice, null);
            set_flash('success', 'Számla törölve.');
        }
        redirect('/invoices');
    }

    /**
     * API: Beszállító autocomplete keresés
     */
    public function searchSuppliers(): void
    {
        Middleware::auth();
        $q = $_GET['q'] ?? '';
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(Supplier::search($q), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Tömeges számla feltöltés form
     */
    public function bulkUploadForm(): void
    {
        Middleware::owner();

        view('layouts/app', [
            'content' => 'invoices/bulk-upload',
            'data' => [
                'pageTitle' => 'Tömeges számla feltöltés',
                'activeTab' => 'szamlak',
                'suppliers' => Supplier::all(),
            ]
        ]);
    }

    /**
     * Tömeges számla feltöltés feldolgozás
     */
    public function bulkUploadStore(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        if (empty($_FILES['invoices']) || !is_array($_FILES['invoices']['name'])) {
            set_flash('error', 'Kérem válasszon legalább egy fájlt.');
            redirect('/invoices/bulk-upload');
        }

        $uploadDir = __DIR__ . '/../../public/uploads/invoices';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $count = 0;
        $fileCount = count($_FILES['invoices']['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['invoices']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $tmpFile = $_FILES['invoices']['tmp_name'][$i];
            $originalName = $_FILES['invoices']['name'][$i];

            // Fájl ELŐSZÖR mentése (mert move_uploaded_file csak egyszer működik)
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $filename = date('Ymd') . '_' . uniqid() . '.' . $ext;
            $tmpSaved = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($tmpFile, $tmpSaved)) continue;

            // Adatok kinyerése AI-val (ha hiba, fallback fájlnévre)
            try {
                $invoiceData = $this->analyzeInvoiceWithAI($tmpSaved, $originalName);
            } catch (\Throwable $e) {
                $invoiceData = [
                    'supplier' => pathinfo($originalName, PATHINFO_FILENAME),
                    'invoice_number' => pathinfo($originalName, PATHINFO_FILENAME),
                    'amount' => 0, 'net_amount' => 0, 'currency' => 'HUF',
                    'date' => date('Y-m-d'), 'failed' => false,
                ];
            }

            // Sikertelen számla kihagyása
            if (!empty($invoiceData['failed'])) {
                unlink($tmpSaved);
                continue;
            }

            // Beszállító (findOrCreate: ha már létezik, nem hozza létre újra)
            $supplierName = $invoiceData['supplier'] ?? pathinfo($originalName, PATHINFO_FILENAME);
            $supplierId = Supplier::findOrCreate($supplierName);

            // Duplikátum ellenőrzés (számla szám + beszállító)
            $invoiceNum = $invoiceData['invoice_number'] ?? pathinfo($originalName, PATHINFO_FILENAME);
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare('SELECT COUNT(*) FROM invoices WHERE supplier_id = :sid AND invoice_number = :num');
            $stmt->execute(['sid' => $supplierId, 'num' => $invoiceNum]);
            if ((int)$stmt->fetchColumn() > 0) {
                unlink($tmpSaved);
                continue;
            }

            // Végleges helyre mozgatás (havi mappa)
            $monthDir = date('Y-m', strtotime($invoiceData['date'] ?? date('Y-m-d')));
            $saveDir = $uploadDir . '/' . $monthDir;
            if (!is_dir($saveDir)) mkdir($saveDir, 0755, true);
            rename($tmpSaved, $saveDir . '/' . $filename);

            $data = [
                'store_id'       => null,
                'supplier_id'    => $supplierId,
                'invoice_number' => $invoiceData['invoice_number'] ?? pathinfo($originalName, PATHINFO_FILENAME),
                'net_amount'     => $invoiceData['net_amount'] ?? 0,
                'amount'         => $invoiceData['amount'] ?? 0,
                'currency'       => $invoiceData['currency'] ?? 'HUF',
                'invoice_date'   => $invoiceData['date'] ?? date('Y-m-d'),
                'due_date'       => null,
                'payment_method' => $invoiceData['payment_method'] ?? 'kartya',
                'notes'          => 'Tömeges feltöltés: ' . $originalName,
                'recorded_by'    => Auth::id(),
            ];

            $id = Invoice::create($data);
            Invoice::updateImage($id, 'uploads/invoices/' . $monthDir . '/' . $filename);
            AuditLog::log('create', 'invoices', $id, null, $data);
            $count++;
        }

        set_flash('success', $count . ' számla sikeresen feltöltve. Ellenőrizze az összegeket és kösse össze a tranzakciókkal.');
        redirect('/invoices');
    }

    /**
     * Számla feldolgozása Claude AI-val
     */
    private function analyzeInvoiceWithAI(string $filePath, string $originalName): array
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $default = [
            'supplier'       => $name,
            'invoice_number' => $name,
            'amount'         => 0,
            'net_amount'     => 0,
            'currency'       => 'HUF',
            'date'           => date('Y-m-d'),
            'failed'         => false,
        ];

        $apiKey = \App\Models\CompanySetting::get('anthropic_api_key', '');
        if (!$apiKey) return $default;

        $fileData = file_get_contents($filePath);
        if (!$fileData) return $default;

        $prompt = "Elemezd ezt a számlát/invoice-t. Válaszolj KIZÁRÓLAG JSON formátumban:\n\n";
        $prompt .= '{"supplier_name":"a kiállító/eladó cég neve","invoice_number":"számla szám","net_amount":0,"gross_amount":0,"currency":"HUF","invoice_date":"YYYY-MM-DD","payment_method":"kartya","is_failed":false}' . "\n\n";
        $prompt .= "FONTOS: is_failed CSAK AKKOR true, ha a számla egyértelműen jelzi hogy a fizetés sikertelen volt (unsuccessful, failed, declined, payment failed). Ha a számlán 'Kifizetve' vagy összeg szerepel, az NEM sikertelen!\n";
        $prompt .= "Az 'Az ok nem található' vagy hasonló szöveg NEM jelent sikertelen fizetést.\n";
        $prompt .= "supplier_name: a kiállító cég neve (pl. 'Meta Platforms Ireland Limited', nem a vevő)\n";
        $prompt .= "currency: HUF, EUR vagy USD\n";
        $prompt .= "payment_method: keszpenz, atutalas, kartya, utanvet\n";
        $prompt .= "Ha nem számla, válaszolj: {\"is_invoice\":false}\n";

        $messages = [['role' => 'user', 'content' => [
            ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($fileData)]],
            ['type' => 'text', 'text' => $prompt],
        ]]];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 500,
                'messages' => $messages,
            ]),
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return $default;
        $data = json_decode($response, true);
        $text = $data['content'][0]['text'] ?? '';

        if (!preg_match('/\{.*\}/s', $text, $match)) return $default;
        $ai = json_decode($match[0], true);
        if (!$ai) return $default;

        // Nem számla
        if (isset($ai['is_invoice']) && !$ai['is_invoice']) {
            $default['failed'] = true;
            return $default;
        }

        return [
            'supplier'       => $ai['supplier_name'] ?? $name,
            'invoice_number' => $ai['invoice_number'] ?? $name,
            'amount'         => (float)($ai['gross_amount'] ?? 0),
            'net_amount'     => (float)($ai['net_amount'] ?? $ai['gross_amount'] ?? 0),
            'currency'       => $ai['currency'] ?? 'HUF',
            'date'           => $ai['invoice_date'] ?? date('Y-m-d'),
            'payment_method' => $ai['payment_method'] ?? 'kartya',
            'failed'         => !empty($ai['is_failed']),
        ];
    }

    /**
     * Gmail számlák manuális letöltése
     */
    public function fetchEmails(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        try {
            $processor = new \App\Services\InvoiceEmailProcessor();
            $results = $processor->process();

            $successCount = 0;
            $errorCount = 0;
            foreach ($results as $r) {
                if (($r['status'] ?? '') === 'success') $successCount++;
                if (($r['status'] ?? '') === 'error') $errorCount++;
            }

            if ($successCount > 0) {
                set_flash('success', $successCount . ' számla sikeresen importálva a Gmailből.');
            } elseif ($errorCount > 0) {
                set_flash('error', 'Hiba történt az email feldolgozás során.');
            } else {
                set_flash('info', 'Nincs új számla a Gmailben.');
            }
        } catch (\Throwable $e) {
            set_flash('error', 'Gmail hiba: ' . $e->getMessage());
        }

        redirect('/invoices');
    }

}
