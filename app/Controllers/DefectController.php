<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog, Database};
use App\Models\{DefectItem, Product, Store};

class DefectController
{
    /**
     * Selejt kezeles fooldal (vonalkod szkenner + lista)
     */
    public function index(): void
    {
        Middleware::tabPermission('selejt', 'view');

        $storeId = Auth::isStore() ? Auth::storeId() : ($_GET['store_id'] ?? null);
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $items = DefectItem::all(
            $storeId ? (int)$storeId : null,
            $dateFrom ?: null,
            $dateTo ?: null
        );

        $stores = Auth::isOwner() ? Store::all() : [];

        // Mai selejtek száma és becsült összérték
        $todayItems = DefectItem::all(
            $storeId ? (int)$storeId : null,
            date('Y-m-d'),
            date('Y-m-d')
        );
        $todayCount = count($todayItems);
        $todayEstimatedValue = 0;
        foreach ($todayItems as $ti) {
            $todayEstimatedValue += (float)($ti['product_price'] ?? 0);
        }

        view('layouts/app', [
            'content' => 'defects/index',
            'data' => [
                'pageTitle'           => 'Selejt kezeles',
                'activeTab'           => 'selejt',
                'items'               => $items,
                'todayCount'          => $todayCount,
                'todayEstimatedValue' => $todayEstimatedValue,
                'stores'     => $stores,
                'filters'    => [
                    'store_id'  => $storeId,
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                ],
            ]
        ]);
    }

    /**
     * Vonalkod szkenneles - JSON endpoint (auto-save)
     */
    public function scan(): void
    {
        Middleware::tabPermission('selejt', 'create');
        Middleware::verifyCsrf();

        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true);
        $barcode = trim($input['barcode'] ?? $_POST['barcode'] ?? '');

        if (empty($barcode)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Vonalkod megadasa kotelezo.']);
            return;
        }

        $storeId = Auth::isStore()
            ? Auth::storeId()
            : (int)($input['store_id'] ?? $_POST['store_id'] ?? 0);

        if (!$storeId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Bolt kivalasztasa kotelezo.']);
            return;
        }

        // Termék keresése vonalkód alapján
        $product = Product::findByBarcode($barcode);

        $data = [
            'store_id'      => $storeId,
            'barcode'       => $barcode,
            'product_name'  => $product['name'] ?? null,
            'product_price' => $product['gross_price'] ?? null,
            'scanned_by'    => Auth::id(),
        ];

        $id = DefectItem::create($data);
        $item = DefectItem::find($id);

        AuditLog::log('create', 'defect_items', $id, null, $data);

        echo json_encode([
            'success' => true,
            'item'    => [
                'id'            => $item['id'],
                'barcode'       => $item['barcode'],
                'product_name'  => $item['product_name'],
                'product_price' => $item['product_price'],
                'store_name'    => $item['store_name'],
                'scanned_at'    => $item['scanned_at'],
            ],
        ]);
    }

    /**
     * Selejt tetel torlese (csak tulajdonos)
     */
    public function destroy(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $item = DefectItem::find((int)$id);
        if ($item) {
            DefectItem::delete((int)$id);
            AuditLog::log('delete', 'defect_items', (int)$id, $item, null);
            set_flash('success', 'Selejt tetel torolve.');
        }
        redirect_back('/defects');
    }

    /**
     * Napi selejt összérték rögzítése
     */
    public function saveDailyValue(): void
    {
        Middleware::tabPermission('selejt', 'create');
        Middleware::verifyCsrf();

        $storeId = Auth::isStore() ? Auth::storeId() : (int)($_POST['store_id'] ?? 0);
        $date = $_POST['value_date'] ?? date('Y-m-d');
        $value = (float)($_POST['total_value'] ?? 0);

        if (!$storeId || $value < 0) {
            set_flash('error', 'Hibás adatok.');
            redirect('/defects');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO defect_daily_values (store_id, value_date, total_value, recorded_by)
             VALUES (:store_id, :date, :value, :user)
             ON DUPLICATE KEY UPDATE total_value = :value2, recorded_by = :user2'
        );
        $stmt->execute([
            'store_id' => $storeId,
            'date'     => $date,
            'value'    => $value,
            'user'     => Auth::id(),
            'value2'   => $value,
            'user2'    => Auth::id(),
        ]);

        AuditLog::log('create', 'defect_daily_values', null, null, ['store_id' => $storeId, 'date' => $date, 'value' => $value]);
        set_flash('success', 'Napi selejt érték rögzítve: ' . format_money($value));
        redirect('/defects');
    }

    /**
     * Ellenőrzi van-e napi érték rögzítve (API)
     */
    public function checkDailyValue(): void
    {
        Middleware::auth();
        $storeId = Auth::isStore() ? Auth::storeId() : (int)($_GET['store_id'] ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');

        $db = Database::getInstance();

        // Van-e selejt tétel az adott napra?
        $stmt = $db->prepare('SELECT COUNT(*) FROM defect_items WHERE store_id = :s AND DATE(scanned_at) = :d');
        $stmt->execute(['s' => $storeId, 'd' => $date]);
        $hasDefects = (int)$stmt->fetchColumn() > 0;

        // Van-e napi érték?
        $stmt = $db->prepare('SELECT total_value FROM defect_daily_values WHERE store_id = :s AND value_date = :d');
        $stmt->execute(['s' => $storeId, 'd' => $date]);
        $dailyValue = $stmt->fetch();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'hasDefects' => $hasDefects,
            'hasValue'   => $dailyValue !== false,
            'value'      => $dailyValue ? (float)$dailyValue['total_value'] : null,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * CSV export
     */
    public function export(): void
    {
        Middleware::tabPermission('selejt', 'view');

        $storeId = Auth::isStore() ? Auth::storeId() : ($_GET['store_id'] ?? null);
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $items = DefectItem::getForExport(
            $storeId ? (int)$storeId : null,
            $dateFrom ?: null,
            $dateTo ?: null
        );

        $filename = 'selejt_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM az Excel kompatibilitashoz
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Fejlec sor
        fputcsv($output, ['Vonalkod', 'Termek nev', 'Brutto ar', 'Bolt', 'Szkennelete', 'Datum/Ido'], ';');

        foreach ($items as $item) {
            fputcsv($output, [
                $item['barcode'],
                $item['product_name'] ?? '',
                $item['product_price'] ?? '',
                $item['store_name'],
                $item['scanned_by_name'],
                $item['scanned_at'],
            ], ';');
        }

        fclose($output);
        exit;
    }
}
