<?php

namespace App\Models;

use App\Core\Database;

class Schedule
{
    /**
     * Beosztas lekerese adott bolt es idoszak alapjan (FullCalendar-hoz)
     */
    public static function getByDateRange(int $storeId, string $from, string $to): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT s.*, e.name as employee_name
             FROM schedules s
             JOIN employees e ON s.employee_id = e.id
             WHERE s.store_id = :store_id
             AND s.work_date >= :date_from
             AND s.work_date <= :date_to
             ORDER BY s.work_date, s.shift_start'
        );
        $stmt->execute([
            'store_id'  => $storeId,
            'date_from' => $from,
            'date_to'   => $to,
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Uj beosztas bejegyzes letrehozasa
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO schedules (store_id, employee_id, work_date, shift_start, shift_end, created_by, created_at)
             VALUES (:store_id, :employee_id, :work_date, :shift_start, :shift_end, :created_by, NOW())'
        );
        $stmt->execute([
            'store_id'    => $data['store_id'],
            'employee_id' => $data['employee_id'],
            'work_date'   => $data['work_date'],
            'shift_start' => $data['shift_start'],
            'shift_end'   => $data['shift_end'],
            'created_by'  => $data['created_by'],
        ]);
        return (int) $db->lastInsertId();
    }

    /**
     * Egyetlen beosztas lekerese
     */
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT s.*, e.name as employee_name
             FROM schedules s
             JOIN employees e ON s.employee_id = e.id
             WHERE s.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Beosztas torlese
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('DELETE FROM schedules WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Beosztas athelyezese masik napra (drag & drop)
     */
    public static function move(int $id, string $newDate): bool
    {
        $db = Database::getInstance();
        return $db->prepare(
            'UPDATE schedules SET work_date = :work_date WHERE id = :id'
        )->execute([
            'work_date' => $newDate,
            'id'        => $id,
        ]);
    }

    /**
     * Mai napon dolgozo alkalmazottak egy adott boltban
     */
    public static function getTodayWorkers(int $storeId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT s.*, e.name as employee_name
             FROM schedules s
             JOIN employees e ON s.employee_id = e.id
             WHERE s.store_id = :store_id
             AND s.work_date = CURDATE()
             ORDER BY s.shift_start'
        );
        $stmt->execute(['store_id' => $storeId]);
        return $stmt->fetchAll();
    }
}
