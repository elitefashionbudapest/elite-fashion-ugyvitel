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

        $supplierName = $_POST['supplier_name'] ?? '';
        if ($supplierName === '__new__') {
            $supplierName = trim($_POST['supplier_name_new'] ?? '');
        }
        if (empty($supplierName)) {
            set_flash('error', 'Beszállító megadása kötelező.');
            redirect('/invoices/bulk-upload');
        }

        $paymentMethod = $_POST['payment_method'] ?? 'kartya';
        $currency = $_POST['currency'] ?? 'HUF';

        if (empty($_FILES['invoices']) || !is_array($_FILES['invoices']['name'])) {
            set_flash('error', 'Kérem válasszon legalább egy fájlt.');
            redirect('/invoices/bulk-upload');
        }

        $supplierId = Supplier::findOrCreate($supplierName);
        $uploadDir = __DIR__ . '/../../public/uploads/invoices';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $count = 0;
        $fileCount = count($_FILES['invoices']['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['invoices']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $tmpFile = $_FILES['invoices']['tmp_name'][$i];
            $originalName = $_FILES['invoices']['name'][$i];

            // Számla szám és összeg kinyerése a PDF-ből
            $invoiceData = $this->extractPdfData($tmpFile, $originalName);

            // Fájl mentése
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = date('Ymd') . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($tmpFile, $uploadDir . '/' . $filename);

            $data = [
                'store_id'       => null,
                'supplier_id'    => $supplierId,
                'invoice_number' => $invoiceData['invoice_number'] ?? pathinfo($originalName, PATHINFO_FILENAME),
                'net_amount'     => $invoiceData['net_amount'] ?? 0,
                'amount'         => $invoiceData['amount'] ?? 0,
                'currency'       => $currency,
                'invoice_date'   => $invoiceData['date'] ?? date('Y-m-d'),
                'due_date'       => null,
                'payment_method' => $paymentMethod,
                'notes'          => 'Tömeges feltöltés: ' . $originalName,
                'recorded_by'    => Auth::id(),
            ];

            $id = Invoice::create($data);
            Invoice::updateImage($id, 'uploads/invoices/' . $filename);
            AuditLog::log('create', 'invoices', $id, null, $data);
            $count++;
        }

        set_flash('success', $count . ' számla sikeresen feltöltve. Kérem ellenőrizze az összegeket és kösse össze a tranzakciókkal.');
        redirect('/invoices');
    }

    /**
     * PDF-ből adatok kinyerése (fájlnév és tartalom alapján)
     */
    private function extractPdfData(string $filePath, string $originalName): array
    {
        $result = [
            'invoice_number' => null,
            'amount'         => 0,
            'net_amount'     => 0,
            'date'           => date('Y-m-d'),
        ];

        // Fájlnévből próbáljuk kinyerni
        $name = pathinfo($originalName, PATHINFO_FILENAME);

        // Facebook számla fájlnév minta: "Invoice_12345678" vagy dátumot tartalmazhat
        if (preg_match('/invoice[_\s-]*(\d+)/i', $name, $m)) {
            $result['invoice_number'] = $m[1];
        }
        if (preg_match('/(\d{4})[._-](\d{2})[._-](\d{2})/', $name, $m)) {
            $result['date'] = $m[1] . '-' . $m[2] . '-' . $m[3];
        }

        // PDF szöveg kinyerése (egyszerű módszer)
        $content = file_get_contents($filePath);
        if ($content) {
            // Összeg keresése
            if (preg_match('/(?:Total|Összesen|Amount|Végösszeg)[:\s]*([0-9,.]+)\s*(HUF|EUR|USD|Ft)?/i', $content, $m)) {
                $result['amount'] = (float)str_replace([',', ' '], ['.', ''], $m[1]);
            }
            // Számla szám
            if (!$result['invoice_number'] && preg_match('/(?:Invoice|Számla)[#:\s]*([A-Z0-9-]+)/i', $content, $m)) {
                $result['invoice_number'] = $m[1];
            }
            // Nettó összeg
            if (preg_match('/(?:Subtotal|Nettó|Net)[:\s]*([0-9,.]+)/i', $content, $m)) {
                $result['net_amount'] = (float)str_replace([',', ' '], ['.', ''], $m[1]);
            }
        }

        if (!$result['invoice_number']) {
            $result['invoice_number'] = $name;
        }
        if ($result['net_amount'] == 0) {
            $result['net_amount'] = $result['amount'];
        }

        return $result;
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
