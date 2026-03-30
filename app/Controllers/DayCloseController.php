<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, Database, AuditLog};

class DayCloseController
{
    /**
     * API: Mai hiányzó feladatok lekérése az adott boltra
     */
    public function check(): void
    {
        Middleware::auth();

        $db = Database::getInstance();
        $today = date('Y-m-d');
        $storeId = Auth::isStore() ? Auth::storeId() : ((int)($_GET['store_id'] ?? 0) ?: null);

        if (!$storeId) {
            // Tulajdonosnak az összes bolt
            $stores = $db->query('SELECT id, name, open_days FROM stores ORDER BY name')->fetchAll();
        } else {
            $stmt = $db->prepare('SELECT id, name, open_days FROM stores WHERE id = :id');
            $stmt->execute(['id' => $storeId]);
            $stores = $stmt->fetchAll();
        }

        $missing = [];

        foreach ($stores as $store) {
            $sid = $store['id'];
            $sName = $store['name'];

            // Nyitvatartás ellenőrzés
            $openDays = explode(',', $store['open_days'] ?? '1,2,3,4,5,6');
            if (!in_array((string)date('N'), $openDays)) continue;

            // Készpénz forgalom
            $stmt = $db->prepare("SELECT COUNT(*) FROM financial_records WHERE store_id = :s AND record_date = :d AND purpose = 'napi_keszpenz'");
            $stmt->execute(['s' => $sid, 'd' => $today]);
            if (!(int)$stmt->fetchColumn()) {
                $missing[] = ['store_id' => $sid, 'store' => $sName, 'type' => 'napi_keszpenz', 'label' => 'Készpénz forgalom'];
            }

            // Bankkártya forgalom
            $stmt = $db->prepare("SELECT COUNT(*) FROM financial_records WHERE store_id = :s AND record_date = :d AND purpose = 'napi_bankkartya'");
            $stmt->execute(['s' => $sid, 'd' => $today]);
            if (!(int)$stmt->fetchColumn()) {
                $missing[] = ['store_id' => $sid, 'store' => $sName, 'type' => 'napi_bankkartya', 'label' => 'Bankkártya forgalom'];
            }

            // Értékelés
            $stmt = $db->prepare("SELECT COUNT(*) FROM evaluations WHERE store_id = :s AND record_date = :d");
            $stmt->execute(['s' => $sid, 'd' => $today]);
            if (!(int)$stmt->fetchColumn()) {
                $missing[] = ['store_id' => $sid, 'store' => $sName, 'type' => 'ertekeles', 'label' => 'Értékelés'];
            }

            // Selejt napi érték (csak ha volt selejt)
            $stmt = $db->prepare("SELECT COUNT(*) FROM defect_items WHERE store_id = :s AND DATE(scanned_at) = :d");
            $stmt->execute(['s' => $sid, 'd' => $today]);
            if ((int)$stmt->fetchColumn() > 0) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM defect_daily_values WHERE store_id = :s AND value_date = :d");
                $stmt->execute(['s' => $sid, 'd' => $today]);
                if (!(int)$stmt->fetchColumn()) {
                    $missing[] = ['store_id' => $sid, 'store' => $sName, 'type' => 'selejt_ertek', 'label' => 'Selejt összérték'];
                }
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['missing' => $missing, 'date' => $today], JSON_UNESCAPED_UNICODE);
    }

    /**
     * API: Hiányzó feladatok 0 értékkel rögzítése
     */
    public function close(): void
    {
        Middleware::auth();
        Middleware::verifyCsrf();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $items = $input['items'] ?? [];
        $today = date('Y-m-d');
        $db = Database::getInstance();
        $userId = Auth::id();
        $count = 0;

        foreach ($items as $item) {
            $storeId = (int)($item['store_id'] ?? 0);
            $type = $item['type'] ?? '';
            if (!$storeId || !$type) continue;

            switch ($type) {
                case 'napi_keszpenz':
                case 'napi_bankkartya':
                    // Ellenőrzés hogy ne legyen duplikátum
                    $stmt = $db->prepare("SELECT COUNT(*) FROM financial_records WHERE store_id = :s AND record_date = :d AND purpose = :p");
                    $stmt->execute(['s' => $storeId, 'd' => $today, 'p' => $type]);
                    if (!(int)$stmt->fetchColumn()) {
                        $stmt = $db->prepare("INSERT INTO financial_records (store_id, recorded_by, record_date, purpose, amount) VALUES (:s, :u, :d, :p, 0)");
                        $stmt->execute(['s' => $storeId, 'u' => $userId, 'd' => $today, 'p' => $type]);
                        $count++;
                    }
                    break;

                case 'ertekeles':
                    $stmt = $db->prepare("SELECT COUNT(*) FROM evaluations WHERE store_id = :s AND record_date = :d");
                    $stmt->execute(['s' => $storeId, 'd' => $today]);
                    if (!(int)$stmt->fetchColumn()) {
                        $stmt = $db->prepare("INSERT INTO evaluations (store_id, recorded_by, record_date, customer_count, google_review_count) VALUES (:s, :u, :d, 0, 0)");
                        $stmt->execute(['s' => $storeId, 'u' => $userId, 'd' => $today]);
                        $count++;
                    }
                    break;

                case 'selejt_ertek':
                    $stmt = $db->prepare("SELECT COUNT(*) FROM defect_daily_values WHERE store_id = :s AND value_date = :d");
                    $stmt->execute(['s' => $storeId, 'd' => $today]);
                    if (!(int)$stmt->fetchColumn()) {
                        $stmt = $db->prepare("INSERT INTO defect_daily_values (store_id, value_date, total_value, recorded_by) VALUES (:s, :d, 0, :u)");
                        $stmt->execute(['s' => $storeId, 'd' => $today, 'u' => $userId]);
                        $count++;
                    }
                    break;
            }
        }

        AuditLog::log('create', 'day_close', null, null, ['date' => $today, 'items' => count($items), 'recorded' => $count]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'count' => $count], JSON_UNESCAPED_UNICODE);
    }
}
