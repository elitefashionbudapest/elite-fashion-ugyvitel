<?php

namespace App\Models;

use App\Core\Database;

class Evaluation
{
    /**
     * Osszes ertekeles lekerese szurokkel
     */
    public static function all(?int $storeId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $db = Database::getInstance();
        $where = [];
        $params = [];

        if ($storeId !== null) {
            $where[] = 'ev.store_id = :store_id';
            $params['store_id'] = $storeId;
        }
        if ($dateFrom !== null) {
            $where[] = 'ev.record_date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo !== null) {
            $where[] = 'ev.record_date <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $sql = 'SELECT ev.*, s.name AS store_name,
                       GROUP_CONCAT(e.name ORDER BY e.name SEPARATOR ", ") AS worker_names
                FROM evaluations ev
                LEFT JOIN stores s ON ev.store_id = s.id
                LEFT JOIN evaluation_workers ew ON ev.id = ew.evaluation_id
                LEFT JOIN employees e ON ew.employee_id = e.id';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY ev.id ORDER BY ev.record_date DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Egyetlen ertekeles lekerese
     */
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT ev.*, s.name AS store_name
             FROM evaluations ev
             LEFT JOIN stores s ON ev.store_id = s.id
             WHERE ev.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Uj ertekeles letrehozasa
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO evaluations (store_id, recorded_by, record_date, customer_count, google_review_count)
             VALUES (:store_id, :recorded_by, :record_date, :customer_count, :google_review_count)'
        );
        $stmt->execute([
            'store_id'            => $data['store_id'],
            'recorded_by'         => $data['recorded_by'] ?? \App\Core\Auth::id(),
            'record_date'         => $data['record_date'],
            'customer_count'      => (int)$data['customer_count'],
            'google_review_count' => (int)$data['google_review_count'],
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Ertekeles torlese (pivot rekorddal egyutt)
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        // Eloszor a pivot tabla
        $db->prepare('DELETE FROM evaluation_workers WHERE evaluation_id = :id')->execute(['id' => $id]);
        return $db->prepare('DELETE FROM evaluations WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Adott ertekeleshez tartozo dolgozok
     */
    public static function getWorkers(int $evaluationId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT e.* FROM employees e
             JOIN evaluation_workers ew ON e.id = ew.employee_id
             WHERE ew.evaluation_id = :evaluation_id
             ORDER BY e.name'
        );
        $stmt->execute(['evaluation_id' => $evaluationId]);
        return $stmt->fetchAll();
    }

    /**
     * Dolgozok hozzarendelese ertekeleshez
     */
    public static function addWorkers(int $evaluationId, array $employeeIds): void
    {
        $db = Database::getInstance();
        // Meglevo hozzarendelesek torlese
        $db->prepare('DELETE FROM evaluation_workers WHERE evaluation_id = :id')
           ->execute(['id' => $evaluationId]);

        // Ujak beillesztese
        $stmt = $db->prepare(
            'INSERT INTO evaluation_workers (evaluation_id, employee_id) VALUES (:eval_id, :emp_id)'
        );
        foreach ($employeeIds as $empId) {
            $stmt->execute([
                'eval_id' => $evaluationId,
                'emp_id'  => (int)$empId,
            ]);
        }
    }

    /**
     * Egy dolgozo premium statusza adott honapban
     * Szamitas: azon napokon ahol dolgozott, az osszes google ertekeles / osszes vasarlo
     */
    public static function getPremiumStatus(int $employeeId, int $month, int $year): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT COALESCE(SUM(ev.customer_count), 0) AS total_customers,
                    COALESCE(SUM(ev.google_review_count), 0) AS total_reviews
             FROM evaluations ev
             JOIN evaluation_workers ew ON ev.id = ew.evaluation_id
             WHERE ew.employee_id = :employee_id
               AND MONTH(ev.record_date) = :month
               AND YEAR(ev.record_date) = :year'
        );
        $stmt->execute([
            'employee_id' => $employeeId,
            'month'       => $month,
            'year'        => $year,
        ]);
        $row = $stmt->fetch();

        $totalCustomers = (int)$row['total_customers'];
        $totalReviews   = (int)$row['total_reviews'];
        $ratio = $totalCustomers > 0 ? $totalReviews / $totalCustomers : 0;

        return [
            'ratio'          => round($ratio, 4),
            'isPremium'      => $ratio >= 0.9,
            'totalCustomers' => $totalCustomers,
            'totalReviews'   => $totalReviews,
        ];
    }

    /**
     * Osszes dolgozo premium statusza adott honapban
     */
    public static function getAllPremiumStatuses(int $month, int $year): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT e.id, e.name,
                    COALESCE(SUM(ev.customer_count), 0) AS total_customers,
                    COALESCE(SUM(ev.google_review_count), 0) AS total_reviews
             FROM employees e
             JOIN evaluation_workers ew ON e.id = ew.employee_id
             JOIN evaluations ev ON ew.evaluation_id = ev.id
             WHERE MONTH(ev.record_date) = :month
               AND YEAR(ev.record_date) = :year
               AND e.is_active = 1
             GROUP BY e.id, e.name
             ORDER BY e.name'
        );
        $stmt->execute([
            'month' => $month,
            'year'  => $year,
        ]);
        $rows = $stmt->fetchAll();

        $results = [];
        foreach ($rows as $row) {
            $totalCustomers = (int)$row['total_customers'];
            $totalReviews   = (int)$row['total_reviews'];
            $ratio = $totalCustomers > 0 ? $totalReviews / $totalCustomers : 0;

            $results[] = [
                'employee_id'    => (int)$row['id'],
                'employee_name'  => $row['name'],
                'ratio'          => round($ratio, 4),
                'isPremium'      => $ratio >= 0.9,
                'totalCustomers' => $totalCustomers,
                'totalReviews'   => $totalReviews,
            ];
        }

        return $results;
    }
}
