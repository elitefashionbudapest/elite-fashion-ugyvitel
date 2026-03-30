<?php

namespace App\Models;

use App\Core\Database;

class SalaryPayment
{
    public const SOURCES = [
        'bank'        => 'Bank',
        'vorosmarty'  => 'Vörösmarty',
        'selmeci'     => 'Selmeci',
        'ulloi_ut'    => 'Üllői út',
        'egyeb'       => 'Egyéb',
    ];

    public const ISSUERS = [
        'imi'  => 'Imi',
        'adam' => 'Adam',
    ];

    public static function all(?int $employeeId = null, ?int $year = null, ?int $month = null): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT sp.*, e.name as employee_name
                FROM salary_payments sp
                JOIN employees e ON sp.employee_id = e.id
                WHERE 1=1';
        $params = [];

        if ($employeeId) {
            $sql .= ' AND sp.employee_id = :employee_id';
            $params['employee_id'] = $employeeId;
        }
        if ($year) {
            $sql .= ' AND sp.year = :year';
            $params['year'] = $year;
        }
        if ($month) {
            $sql .= ' AND sp.month = :month';
            $params['month'] = $month;
        }

        $sql .= ' ORDER BY sp.year DESC, sp.month DESC, sp.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT sp.*, e.name as employee_name
             FROM salary_payments sp
             JOIN employees e ON sp.employee_id = e.id
             WHERE sp.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO salary_payments (employee_id, issuer, year, month, source, amount, created_by)
             VALUES (:employee_id, :issuer, :year, :month, :source, :amount, :created_by)'
        );
        $stmt->execute([
            'employee_id' => $data['employee_id'],
            'issuer'      => $data['issuer'],
            'year'        => $data['year'],
            'month'       => $data['month'],
            'source'      => $data['source'],
            'amount'      => $data['amount'],
            'created_by'  => $data['created_by'],
        ]);
        return (int)$db->lastInsertId();
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('DELETE FROM salary_payments WHERE id = :id')->execute(['id' => $id]);
    }
}
