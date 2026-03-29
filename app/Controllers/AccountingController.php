<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog, Database};
use App\Models\{Bank, Employee};

class AccountingController
{
    public function index(): void
    {
        Middleware::tabPermission('konyvelo_docs', 'view');

        $db = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = !empty($_GET['month']) ? (int)$_GET['month'] : null;

        // Bankszámlakivonatok
        $stmtSql = "SELECT bs.*, b.name as bank_name, u.name as uploaded_by_name
                     FROM bank_statements bs
                     JOIN banks b ON bs.bank_id = b.id
                     JOIN users u ON bs.uploaded_by = u.id
                     WHERE bs.year = :year";
        $params = ['year' => $year];
        if ($month) {
            $stmtSql .= " AND bs.month = :month";
            $params['month'] = $month;
        }
        $stmtSql .= " ORDER BY bs.month DESC, b.name";
        $stmt = $db->prepare($stmtSql);
        $stmt->execute($params);
        $statements = $stmt->fetchAll();

        // Bérpapírok
        $paySql = "SELECT p.*, e.name as employee_name, u.name as uploaded_by_name
                   FROM payslips p
                   JOIN employees e ON p.employee_id = e.id
                   JOIN users u ON p.uploaded_by = u.id
                   WHERE p.year = :year";
        $payParams = ['year' => $year];
        if ($month) {
            $paySql .= " AND p.month = :month";
            $payParams['month'] = $month;
        }
        $paySql .= " ORDER BY p.month DESC, e.name";
        $stmt = $db->prepare($paySql);
        $stmt->execute($payParams);
        $payslips = $stmt->fetchAll();

        // Számlák szűrve (teljesítési dátum)
        $invDateFrom = $_GET['inv_date_from'] ?? '';
        $invDateTo = $_GET['inv_date_to'] ?? '';

        $banks = Bank::all();
        $employees = Employee::allActive();

        view('layouts/app', [
            'content' => 'accounting/index',
            'data' => [
                'pageTitle'   => 'Könyvelői dokumentumok',
                'activeTab'   => 'konyvelo_docs',
                'year'        => $year,
                'month'       => $month,
                'statements'  => $statements,
                'payslips'    => $payslips,
                'banks'       => $banks,
                'employees'   => $employees,
                'invDateFrom' => $invDateFrom,
                'invDateTo'   => $invDateTo,
            ]
        ]);
    }

    /**
     * Bankszámlakivonat feltöltése (tulajdonos)
     */
    public function uploadStatement(): void
    {
        Middleware::tabPermission('konyvelo_docs', 'create');
        Middleware::verifyCsrf();

        $bankId = (int)($_POST['bank_id'] ?? 0);
        $year = (int)($_POST['year'] ?? date('Y'));
        $month = (int)($_POST['month'] ?? date('m'));

        if (!$bankId || !$year || !$month || empty($_FILES['statement_file']['tmp_name'])) {
            set_flash('error', 'Bank, dátum és fájl megadása kötelező.');
            redirect('/accounting');
        }

        $file = $_FILES['statement_file'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowedMimes[$mime]) || $file['size'] > 20 * 1024 * 1024) {
            set_flash('error', 'Csak PDF, JPG vagy PNG fájl tölthető fel (max 20MB).');
            redirect('/accounting');
        }

        $ext = $allowedMimes[$mime];
        $uploadDir = __DIR__ . '/../../public/uploads/statements/' . $year;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO bank_statements (bank_id, year, month, file_path, original_name, uploaded_by)
                 VALUES (:bank_id, :year, :month, :path, :orig, :user)
                 ON DUPLICATE KEY UPDATE file_path = :path2, original_name = :orig2, uploaded_by = :user2'
            );
            $stmt->execute([
                'bank_id' => $bankId, 'year' => $year, 'month' => $month,
                'path' => '/uploads/statements/' . $year . '/' . $filename,
                'orig' => $file['name'], 'user' => Auth::id(),
                'path2' => '/uploads/statements/' . $year . '/' . $filename,
                'orig2' => $file['name'], 'user2' => Auth::id(),
            ]);
            AuditLog::log('create', 'bank_statements', null, null, ['bank_id' => $bankId, 'year' => $year, 'month' => $month]);
            set_flash('success', 'Bankszámlakivonat feltöltve.');
        } else {
            set_flash('error', 'Fájl feltöltés sikertelen.');
        }
        redirect('/accounting?year=' . $year);
    }

    /**
     * Bérpapír feltöltése (könyvelő)
     */
    public function uploadPayslip(): void
    {
        Middleware::tabPermission('konyvelo_docs', 'create');
        Middleware::verifyCsrf();

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $year = (int)($_POST['year'] ?? date('Y'));
        $month = (int)($_POST['month'] ?? date('m'));

        if (!$employeeId || !$year || !$month || empty($_FILES['payslip_file']['tmp_name'])) {
            set_flash('error', 'Dolgozó, dátum és fájl megadása kötelező.');
            redirect('/accounting');
        }

        $file = $_FILES['payslip_file'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowedMimes[$mime]) || $file['size'] > 20 * 1024 * 1024) {
            set_flash('error', 'Csak PDF, JPG vagy PNG fájl tölthető fel (max 20MB).');
            redirect('/accounting');
        }

        $ext = $allowedMimes[$mime];
        $uploadDir = __DIR__ . '/../../public/uploads/payslips/' . $year;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO payslips (employee_id, year, month, file_path, original_name, uploaded_by)
                 VALUES (:emp, :year, :month, :path, :orig, :user)
                 ON DUPLICATE KEY UPDATE file_path = :path2, original_name = :orig2, uploaded_by = :user2'
            );
            $stmt->execute([
                'emp' => $employeeId, 'year' => $year, 'month' => $month,
                'path' => '/uploads/payslips/' . $year . '/' . $filename,
                'orig' => $file['name'], 'user' => Auth::id(),
                'path2' => '/uploads/payslips/' . $year . '/' . $filename,
                'orig2' => $file['name'], 'user2' => Auth::id(),
            ]);
            AuditLog::log('create', 'payslips', null, null, ['employee_id' => $employeeId, 'year' => $year, 'month' => $month]);
            set_flash('success', 'Bérpapír feltöltve.');
        } else {
            set_flash('error', 'Fájl feltöltés sikertelen.');
        }
        redirect('/accounting?year=' . $year);
    }

    /**
     * Számlák letöltése teljesítési dátum alapján (ZIP: lista + képek)
     */
    public function downloadInvoices(): void
    {
        Middleware::tabPermission('konyvelo_docs', 'view');

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        if (!$dateFrom || !$dateTo) {
            set_flash('error', 'Adja meg a teljesítési dátum időszakot.');
            redirect('/accounting');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT i.*, sp.name as supplier_name, s.name as store_name, b.name as bank_name
             FROM invoices i
             JOIN suppliers sp ON i.supplier_id = sp.id
             LEFT JOIN stores s ON i.store_id = s.id
             LEFT JOIN banks b ON i.paid_from_bank_id = b.id
             WHERE i.invoice_date BETWEEN :df AND :dt
             ORDER BY i.invoice_date"
        );
        $stmt->execute(['df' => $dateFrom, 'dt' => $dateTo]);
        $invoices = $stmt->fetchAll();

        if (empty($invoices)) {
            set_flash('error', 'Nincs számla a megadott időszakban.');
            redirect('/accounting');
        }

        // Excel lista (PhpSpreadsheet)
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bejövő számlák');

        // Fejléc
        $headers = ['Számla szám', 'Beszállító', 'Bolt', 'Nettó', 'Bruttó', 'ÁFA', 'Pénznem', 'Teljesítés', 'Határidő', 'Fizetve', 'Fizetés módja', 'Bank'];
        foreach ($headers as $col => $h) {
            $sheet->setCellValue([$col + 1, 1], $h);
        }

        // Fejléc formázás
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '506300']],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        // Adatok
        $row = 2;
        foreach ($invoices as $inv) {
            $vat = (float)$inv['amount'] - (float)$inv['net_amount'];
            $sheet->setCellValue([1, $row], $inv['invoice_number']);
            $sheet->setCellValue([2, $row], $inv['supplier_name']);
            $sheet->setCellValue([3, $row], $inv['store_name'] ?? 'Céges');
            $sheet->setCellValue([4, $row], (float)$inv['net_amount']);
            $sheet->setCellValue([5, $row], (float)$inv['amount']);
            $sheet->setCellValue([6, $row], $vat);
            $sheet->setCellValue([7, $row], $inv['currency']);
            $sheet->setCellValue([8, $row], $inv['invoice_date']);
            $sheet->setCellValue([9, $row], $inv['due_date'] ?? '');
            $sheet->setCellValue([10, $row], $inv['is_paid'] ? 'Igen' : 'Nem');
            $sheet->setCellValue([11, $row], \App\Models\Invoice::PAYMENT_METHODS[$inv['payment_method']] ?? $inv['payment_method']);
            $sheet->setCellValue([12, $row], $inv['bank_name'] ?? '');
            $row++;
        }

        // Szám formátum
        $lastRow = $row - 1;
        $sheet->getStyle("D2:F{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        // Oszlop szélesség
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Temp fájlba mentés
        $tmpExcel = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tmpExcel);

        // ZIP: Excel + képek
        $zip = new \ZipArchive();
        $tmpFile = tempnam(sys_get_temp_dir(), 'inv_');
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFile($tmpExcel, 'bejovo_szamlak_' . $dateFrom . '_' . $dateTo . '.xlsx');

        // Képek hozzáadása
        $projectDir = __DIR__ . '/../../public';
        foreach ($invoices as $inv) {
            if (!empty($inv['image_path'])) {
                $imgPath = $projectDir . $inv['image_path'];
                if (file_exists($imgPath)) {
                    $zip->addFile($imgPath, 'kepek/' . $inv['invoice_number'] . '_' . basename($imgPath));
                }
            }
        }

        $zip->close();
        unlink($tmpExcel);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="bejovo_szamlak_' . $dateFrom . '_' . $dateTo . '.zip"');
        header('Content-Length: ' . filesize($tmpFile));
        readfile($tmpFile);
        unlink($tmpFile);
        exit;
    }

}
