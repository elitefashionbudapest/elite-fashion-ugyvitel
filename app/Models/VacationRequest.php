<?php

namespace App\Models;

use App\Core\Database;

class VacationRequest
{
    /**
     * Osszes szabadsag kerveny lekerese, opcionalis bolt- es statusz-szures
     */
    public static function all(?int $storeId = null, ?string $status = null, ?int $employeeId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $db = Database::getInstance();

        $sql = 'SELECT vr.*, e.name as employee_name, u.name as approved_by_name
                FROM vacation_requests vr
                JOIN employees e ON vr.employee_id = e.id
                LEFT JOIN users u ON vr.approved_by = u.id';

        $conditions = [];
        $params = [];

        if ($storeId !== null) {
            $conditions[] = 'EXISTS (SELECT 1 FROM employee_store es WHERE es.employee_id = vr.employee_id AND es.store_id = :store_id)';
            $params['store_id'] = $storeId;
        }

        if ($status !== null) {
            $conditions[] = 'vr.status = :status';
            $params['status'] = $status;
        }

        if ($employeeId !== null) {
            $conditions[] = 'vr.employee_id = :employee_id';
            $params['employee_id'] = $employeeId;
        }

        if ($dateFrom !== null) {
            $conditions[] = 'vr.date_to >= :date_from';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo !== null) {
            $conditions[] = 'vr.date_from <= :date_to';
            $params['date_to'] = $dateTo;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY vr.date_from DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Egyetlen kerveny lekerese
     */
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT vr.*, e.name as employee_name, u.name as approved_by_name
             FROM vacation_requests vr
             JOIN employees e ON vr.employee_id = e.id
             LEFT JOIN users u ON vr.approved_by = u.id
             WHERE vr.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Uj kerveny letrehozasa (ellenorzi az atfedest!)
     */
    public static function create(array $data): int
    {
        // Ellenorzes: nincs-e mar jovahagyott szabadsag ebben az idoszakban
        if (self::hasOverlap($data['date_from'], $data['date_to'])) {
            throw new \RuntimeException('Ebben az idoszakban mar van jovahagyott szabadsag. Egyszerre csak 1 fo lehet szabadsagon!');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO vacation_requests (employee_id, date_from, date_to, status, confirmed_no_overlap, created_at)
             VALUES (:employee_id, :date_from, :date_to, :status, :confirmed_no_overlap, NOW())'
        );
        $stmt->execute([
            'employee_id'          => $data['employee_id'],
            'date_from'            => $data['date_from'],
            'date_to'              => $data['date_to'],
            'status'               => 'pending',
            'confirmed_no_overlap' => $data['confirmed_no_overlap'] ?? 0,
        ]);
        return (int) $db->lastInsertId();
    }

    /**
     * Kerveny jovahagyasa (ellenorzi az atfedest az approve pillanataban is!)
     */
    public static function approve(int $id, int $approvedByUserId): bool
    {
        $request = self::find($id);
        if (!$request || $request['status'] !== 'pending') {
            return false;
        }

        // Meg egyszer ellenorizzuk az atfedest jovahagyas elott
        if (self::hasOverlap($request['date_from'], $request['date_to'], $id)) {
            throw new \RuntimeException('Nem hagyhato jova: ebben az idoszakban mar van jovahagyott szabadsag.');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'UPDATE vacation_requests SET status = :status, approved_by = :approved_by, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([
            'status'      => 'approved',
            'approved_by' => $approvedByUserId,
            'id'          => $id,
        ]);
    }

    /**
     * Kerveny elutasitasa
     */
    public static function reject(int $id, int $approvedByUserId): bool
    {
        $request = self::find($id);
        if (!$request || $request['status'] !== 'pending') {
            return false;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'UPDATE vacation_requests SET status = :status, approved_by = :approved_by, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([
            'status'      => 'rejected',
            'approved_by' => $approvedByUserId,
            'id'          => $id,
        ]);
    }

    /**
     * Kerveny torlese
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('DELETE FROM vacation_requests WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Van-e atfedes jovahagyott szabadsaggal a megadott idoszakban?
     * KRITIKUS: az egesz cegre nezve max 1 fo lehet szabadsagon egyszerre!
     */
    public static function hasOverlap(string $dateFrom, string $dateTo, ?int $excludeId = null): bool
    {
        $db = Database::getInstance();

        $sql = "SELECT COUNT(*) FROM vacation_requests
                WHERE status = 'approved'
                AND date_from <= :date_to
                AND date_to >= :date_from";
        $params = [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Jovahagyott szabadsagok lekerese egy adott idoszakra (beosztas nezethez)
     */
    public static function getApprovedForDateRange(string $dateFrom, string $dateTo): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT vr.*, e.name as employee_name
             FROM vacation_requests vr
             JOIN employees e ON vr.employee_id = e.id
             WHERE vr.status = 'approved'
             AND vr.date_from <= :date_to
             AND vr.date_to >= :date_from
             ORDER BY vr.date_from"
        );
        $stmt->execute([
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Az adott dolgozo szabadsagon van-e az adott napon?
     */
    public static function isEmployeeOnVacation(int $employeeId, string $date): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM vacation_requests
             WHERE employee_id = :employee_id
             AND status = 'approved'
             AND date_from <= :date_to
             AND date_to >= :date_from"
        );
        $stmt->execute([
            'employee_id' => $employeeId,
            'date_from'   => $date,
            'date_to'     => $date,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
