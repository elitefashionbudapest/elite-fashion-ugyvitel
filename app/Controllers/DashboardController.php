<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Database;

class DashboardController
{
    public function index(): void
    {
        Middleware::auth();

        $data = [
            'pageTitle' => 'Kezdőlap',
            'activeTab' => 'dashboard',
        ];

        if (Auth::isOwner()) {
            $data = array_merge($data, $this->ownerDashboard());
        } else {
            $data = array_merge($data, $this->storeDashboard());
        }

        view('layouts/app', ['content' => 'dashboard/index', 'data' => $data]);
    }

    private function ownerDashboard(): array
    {
        $db = Database::getInstance();

        $storeCount = (int)$db->query('SELECT COUNT(*) FROM stores')->fetchColumn();
        $employeeCount = (int)$db->query('SELECT COUNT(*) FROM employees WHERE is_active = 1')->fetchColumn();

        $todayRevenue = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM financial_records
             WHERE record_date = CURDATE() AND purpose IN ('napi_keszpenz', 'napi_bankkartya')"
        );
        $todayRevenue->execute();

        $monthlyByStore = $db->query(
            "SELECT s.name, COALESCE(SUM(f.amount), 0) as total
             FROM stores s
             LEFT JOIN financial_records f ON s.id = f.store_id
                AND f.record_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                AND f.purpose IN ('napi_keszpenz', 'napi_bankkartya')
             GROUP BY s.id, s.name
             ORDER BY s.name"
        )->fetchAll();

        // Kasszában lévő pénz boltonként
        // Kassza nyitó + befizetések (bankból+boltból) + készpénz forgalom - kiadások
        $kasszaByStore = $db->query(
            "SELECT s.id, s.name,
                COALESCE(SUM(CASE WHEN f.purpose IN ('kassza_nyito', 'befizetes_bankbol', 'befizetes_boltbol', 'napi_keszpenz', 'selejt_befizetes') THEN f.amount ELSE 0 END), 0)
                - COALESCE(SUM(CASE WHEN f.purpose IN ('meretre_igazitas', 'tankolas', 'munkaber', 'egyeb_kifizetes', 'szamla_kifizetes', 'bank_kifizetes') THEN f.amount ELSE 0 END), 0)
                as kassza_egyenleg
             FROM stores s
             LEFT JOIN financial_records f ON s.id = f.store_id
             GROUP BY s.id, s.name
             ORDER BY s.name"
        )->fetchAll();

        // Értékelések dolgozónként (aktuális hónap)
        $employeeEvals = $db->query(
            "SELECT e.id, e.name,
                COALESCE(SUM(ev.google_review_count), 0) as total_reviews,
                COALESCE(SUM(ev.customer_count), 0) as total_customers,
                COUNT(DISTINCT ev.id) as work_days
             FROM employees e
             LEFT JOIN evaluation_workers ew ON e.id = ew.employee_id
             LEFT JOIN evaluations ev ON ew.evaluation_id = ev.id
                AND ev.record_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                AND ev.record_date <= LAST_DAY(CURDATE())
             WHERE e.is_active = 1
             GROUP BY e.id, e.name
             ORDER BY total_reviews DESC"
        )->fetchAll();

        // Kassza eltérés ellenőrzés boltonként
        // Tegnapi: kassza_nyito + napi_keszpenz + befizetes - kiadások = számított záró
        // Mai kassza_nyito vs számított záró
        $kasszaAlerts = [];
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');

        $stores = $db->query('SELECT id, name FROM stores ORDER BY name')->fetchAll();
        foreach ($stores as $store) {
            $sid = $store['id'];

            // Tegnapi kassza nyitó
            $stmt = $db->prepare("SELECT amount FROM financial_records WHERE store_id = :s AND record_date = :d AND purpose = 'kassza_nyito' LIMIT 1");
            $stmt->execute(['s' => $sid, 'd' => $yesterday]);
            $yesterdayOpen = $stmt->fetchColumn();

            if ($yesterdayOpen === false) continue; // Nem volt tegnap nyitó, nem tudunk számolni

            // Tegnapi bevételek (kassza szempontjából)
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_records WHERE store_id = :s AND record_date = :d AND purpose IN ('napi_keszpenz', 'befizetes_bankbol', 'befizetes_boltbol')");
            $stmt->execute(['s' => $sid, 'd' => $yesterday]);
            $yesterdayIn = (float)$stmt->fetchColumn();

            // Tegnapi kiadások (kassza szempontjából)
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_records WHERE store_id = :s AND record_date = :d AND purpose IN ('meretre_igazitas', 'tankolas', 'munkaber', 'egyeb_kifizetes', 'szamla_kifizetes', 'bank_kifizetes')");
            $stmt->execute(['s' => $sid, 'd' => $yesterday]);
            $yesterdayOut = (float)$stmt->fetchColumn();

            $expectedClose = (float)$yesterdayOpen + $yesterdayIn - $yesterdayOut;

            // Mai kassza nyitó
            $stmt = $db->prepare("SELECT amount FROM financial_records WHERE store_id = :s AND record_date = :d AND purpose = 'kassza_nyito' LIMIT 1");
            $stmt->execute(['s' => $sid, 'd' => $today]);
            $todayOpen = $stmt->fetchColumn();

            if ($todayOpen === false) continue; // Ma még nem nyitották

            $diff = (float)$todayOpen - $expectedClose;

            if (abs($diff) > 1) { // >1 Ft eltérés
                $kasszaAlerts[] = [
                    'store_name'    => $store['name'],
                    'expected'      => $expectedClose,
                    'actual'        => (float)$todayOpen,
                    'diff'          => $diff,
                ];
            }
        }

        return [
            'storeCount'     => $storeCount,
            'employeeCount'  => $employeeCount,
            'todayRevenue'   => (float)$todayRevenue->fetchColumn(),
            'monthlyByStore' => $monthlyByStore,
            'kasszaByStore'  => $kasszaByStore,
            'employeeEvals'  => $employeeEvals,
            'kasszaAlerts'   => $kasszaAlerts,
        ];
    }

    private function storeDashboard(): array
    {
        $storeId = Auth::storeId();
        $db = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM financial_records
             WHERE store_id = :store_id AND record_date = CURDATE()
             AND purpose IN ('napi_keszpenz', 'napi_bankkartya')"
        );
        $stmt->execute(['store_id' => $storeId]);
        $todayRevenue = (float)$stmt->fetchColumn();

        $stmt = $db->prepare(
            'SELECT e.name FROM schedules sc
             JOIN employees e ON sc.employee_id = e.id
             WHERE sc.store_id = :store_id AND sc.work_date = CURDATE()'
        );
        $stmt->execute(['store_id' => $storeId]);
        $todayWorkers = $stmt->fetchAll();

        // Kasszában lévő pénz (saját bolt)
        $stmt = $db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN purpose IN ('kassza_nyito', 'befizetes_bankbol', 'befizetes_boltbol', 'napi_keszpenz', 'selejt_befizetes') THEN amount ELSE 0 END), 0)
                - COALESCE(SUM(CASE WHEN purpose IN ('meretre_igazitas', 'tankolas', 'munkaber', 'egyeb_kifizetes', 'szamla_kifizetes', 'bank_kifizetes') THEN amount ELSE 0 END), 0)
                as kassza_egyenleg
             FROM financial_records WHERE store_id = :store_id"
        );
        $stmt->execute(['store_id' => $storeId]);
        $kasszaEgyenleg = (float)$stmt->fetchColumn();

        // Értékelések dolgozónként (aktuális hónap, saját bolt)
        $stmt = $db->prepare(
            "SELECT e.id, e.name,
                COALESCE(SUM(ev.google_review_count), 0) as total_reviews,
                COALESCE(SUM(ev.customer_count), 0) as total_customers,
                COUNT(DISTINCT ev.id) as work_days
             FROM employees e
             LEFT JOIN evaluation_workers ew ON e.id = ew.employee_id
             LEFT JOIN evaluations ev ON ew.evaluation_id = ev.id
                AND ev.store_id = :store_id
                AND ev.record_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                AND ev.record_date <= LAST_DAY(CURDATE())
             WHERE e.is_active = 1
             GROUP BY e.id, e.name
             ORDER BY total_reviews DESC"
        );
        $stmt->execute(['store_id' => $storeId]);
        $employeeEvals = $stmt->fetchAll();

        // Új bérpapírok a bolt dolgozóinak (aktuális hónap)
        $stmt = $db->prepare(
            "SELECT p.id, e.name as employee_name, p.file_path, p.year, p.month
             FROM payslips p
             JOIN employees e ON p.employee_id = e.id
             JOIN employee_store es ON e.id = es.employee_id AND es.store_id = :store_id
             WHERE p.year = :year AND p.month = :month
             ORDER BY e.name"
        );
        $stmt->execute(['store_id' => $storeId, 'year' => (int)date('Y'), 'month' => (int)date('m')]);
        $newPayslips = $stmt->fetchAll();

        return [
            'todayRevenue'   => $todayRevenue,
            'todayWorkers'   => $todayWorkers,
            'kasszaEgyenleg' => $kasszaEgyenleg,
            'employeeEvals'  => $employeeEvals,
            'newPayslips'    => $newPayslips,
        ];
    }
}
