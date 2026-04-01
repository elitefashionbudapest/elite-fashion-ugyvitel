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

            // Adatok kinyerése a mentett PDF-ből
            $invoiceData = $this->extractPdfData($tmpSaved, $originalName);

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
                'payment_method' => 'kartya',
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
     * PDF-ből adatok kinyerése (beszállító, összeg, dátum, számla szám)
     */
    private function extractPdfData(string $filePath, string $originalName): array
    {
        $result = [
            'supplier'       => null,
            'invoice_number' => null,
            'amount'         => 0,
            'net_amount'     => 0,
            'currency'       => 'HUF',
            'date'           => date('Y-m-d'),
        ];

        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $nameLower = mb_strtolower($name);

        // Fájlnévből adatok
        if (preg_match('/invoice[_\s-]*(\d+)/i', $name, $m)) {
            $result['invoice_number'] = $m[1];
        }
        if (preg_match('/(\d{4})[._-](\d{2})[._-](\d{2})/', $name, $m)) {
            $result['date'] = $m[1] . '-' . $m[2] . '-' . $m[3];
        }

        // 1) Beszállító felismerés FÁJLNÉVBŐL (legmegbízhatóbb)
        $filenameSuppliers = [
            'Facebook'             => ['facebook', 'meta'],
            'Google Ads'           => ['google'],
            'TikTok'               => ['tiktok'],
            'Telenor'              => ['telenor', 'yettel'],
            'Vodafone'             => ['vodafone'],
            'Telekom'              => ['telekom'],
            'DPD'                  => ['dpd'],
            'GLS'                  => ['gls'],
            'FoxPost'              => ['foxpost'],
            'PayPal'               => ['paypal'],
            'Shopify'              => ['shopify'],
            'Mysoft Kft.'          => ['mysoft'],
        ];

        foreach ($filenameSuppliers as $supplierName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($nameLower, $keyword)) {
                    $result['supplier'] = $supplierName;
                    break 2;
                }
            }
        }

        // PDF szöveg kinyerése
        $textContent = $this->extractPdfText($filePath);

        if (!$textContent) {
            $result['supplier'] = $result['supplier'] ?? $name;
            $result['invoice_number'] = $result['invoice_number'] ?? $name;
            return $result;
        }

        // 2) Ha fájlnévből nem találtuk meg, PDF tartalomból (csak hosszabb, specifikus nevek)
        if (!$result['supplier']) {
            $contentSuppliers = [
                'Facebook'             => ['facebook ireland', 'facebook payments', 'meta platforms', 'meta ireland'],
                'Google Ads'           => ['google ads', 'google ireland', 'google llc', 'google payment', 'google cloud'],
                'TikTok'               => ['tiktok', 'bytedance'],
                'Microsoft Advertising'=> ['microsoft advertising', 'microsoft ireland', 'bing ads'],
                'Telenor'              => ['telenor magyarország', 'yettel magyarország'],
                'Yettel'               => ['yettel magyarország'],
                'Vodafone'             => ['vodafone magyarország'],
                'Telekom'              => ['magyar telekom'],
                'ELMŰ'                 => ['elmű-émász', 'e.on energiakereskedelmi'],
                'FoxPost'              => ['foxpost'],
                'Shopify'              => ['shopify'],
                'Stripe'               => ['stripe payments', 'stripe technology'],
                'PayPal'               => ['paypal europe'],
                'Amazon'               => ['amazon eu', 'amazon europe'],
                'Mysoft Kft.'          => ['mysoft kft'],
                'DPD'                  => ['dpd hungária', 'dpd hungary'],
                'GLS'                  => ['gls general logistics'],
            ];

            $contentLower = mb_strtolower($textContent);
            foreach ($contentSuppliers as $supplierName => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($contentLower, $keyword)) {
                        $result['supplier'] = $supplierName;
                        break 2;
                    }
                }
            }
        }

        // Összeg keresése
        if (preg_match('/(?:Total|Összesen|Amount Due|Végösszeg|Fizetendő)[:\s]*([0-9.,\s]+)\s*(HUF|EUR|USD|Ft|\$|€)?/i', $content, $m)) {
            $result['amount'] = (float)str_replace([',', ' '], ['.', ''], $m[1]);
            if (!empty($m[2])) {
                $cur = strtoupper(trim($m[2]));
                if ($cur === 'FT') $cur = 'HUF';
                if ($cur === '€') $cur = 'EUR';
                if ($cur === '$') $cur = 'USD';
                $result['currency'] = $cur;
            }
        }

        // Számla szám
        if (!$result['invoice_number']) {
            if (preg_match('/(?:Invoice|Számla|Receipt)\s*(?:#|No\.?|szám)[:\s]*([A-Za-z0-9_-]+)/i', $content, $m)) {
                $result['invoice_number'] = trim($m[1]);
            }
        }

        // Nettó összeg
        if (preg_match('/(?:Subtotal|Nettó|Net Amount)[:\s]*([0-9.,\s]+)/i', $content, $m)) {
            $result['net_amount'] = (float)str_replace([',', ' '], ['.', ''], $m[1]);
        }

        // Dátum a tartalomból ha fájlnévben nem volt
        if ($result['date'] === date('Y-m-d')) {
            if (preg_match('/(?:Invoice Date|Számla kelte|Date)[:\s]*(\d{4})[.\/-](\d{2})[.\/-](\d{2})/i', $content, $m)) {
                $result['date'] = $m[1] . '-' . $m[2] . '-' . $m[3];
            }
        }

        // Sikertelen számla kiszűrése
        if (preg_match('/(?:unsuccessful|failed|sikertelen|declined|elutasítva|not paid|payment failed)/i', $content)) {
            $result['failed'] = true;
        }

        // Fallback értékek
        if (!$result['supplier']) {
            $result['supplier'] = $name;
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

    /**
     * PDF-ből szöveg kinyerése
     * 1) pdftotext (ha elérhető a szerveren)
     * 2) PDF stream-ek dekompresszálása
     */
    private function extractPdfText(string $filePath): string
    {
        // 1) pdftotext (poppler-utils)
        $pdftotext = null;
        foreach (['/usr/bin/pdftotext', '/usr/local/bin/pdftotext'] as $path) {
            if (file_exists($path)) { $pdftotext = $path; break; }
        }
        if ($pdftotext) {
            $tmpTxt = tempnam(sys_get_temp_dir(), 'pdf');
            exec(escapeshellcmd($pdftotext) . ' ' . escapeshellarg($filePath) . ' ' . escapeshellarg($tmpTxt) . ' 2>/dev/null');
            if (file_exists($tmpTxt) && filesize($tmpTxt) > 10) {
                $text = file_get_contents($tmpTxt);
                unlink($tmpTxt);
                return $text;
            }
            if (file_exists($tmpTxt)) unlink($tmpTxt);
        }

        // 2) PHP: PDF stream-ekből szöveg kinyerése
        $raw = file_get_contents($filePath);
        if (!$raw) return '';

        $text = '';

        // Deflate tömörített streamek kibontása
        if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $raw, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if (!$decoded) $decoded = @gzinflate($stream);
                if (!$decoded) continue;

                // Szöveg operátorok kinyerése: (szöveg) Tj, [(...)] TJ
                if (preg_match_all('/\(([^)]+)\)\s*Tj/s', $decoded, $tm)) {
                    $text .= implode(' ', $tm[1]) . ' ';
                }
                if (preg_match_all('/\[([^\]]*)\]\s*TJ/s', $decoded, $tm)) {
                    foreach ($tm[1] as $arr) {
                        if (preg_match_all('/\(([^)]*)\)/', $arr, $parts)) {
                            $text .= implode('', $parts[1]) . ' ';
                        }
                    }
                }
                // BT...ET blokkok
                if (preg_match_all('/BT\s*(.*?)\s*ET/s', $decoded, $blocks)) {
                    foreach ($blocks[1] as $block) {
                        if (preg_match_all('/\(([^)]+)\)/', $block, $parts)) {
                            $text .= implode('', $parts[1]) . ' ';
                        }
                    }
                }
            }
        }

        // Ha nem sikerült stream-ekből, fallback: bináris-ból olvasható szövegek
        if (strlen($text) < 20) {
            // Csak ASCII-olvasható részeket szűrjük ki
            if (preg_match_all('/[A-Za-z0-9áéíóöőúüűÁÉÍÓÖŐÚÜŰ.,\s@#:\/\-]{5,}/', $raw, $readable)) {
                $text = implode(' ', $readable[0]);
            }
        }

        return $text;
    }
}
