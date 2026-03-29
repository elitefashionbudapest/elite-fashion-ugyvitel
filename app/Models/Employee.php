<?php

namespace App\Models;

use App\Core\Database;

class Employee
{
    public static function all(): array
    {
        $db = Database::getInstance();
        return $db->query(
            'SELECT e.*, GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ", ") as store_names
             FROM employees e
             LEFT JOIN employee_store es ON e.id = es.employee_id
             LEFT JOIN stores s ON es.store_id = s.id
             GROUP BY e.id
             ORDER BY e.name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM employees WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function getByStore(int $storeId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT e.* FROM employees e
             JOIN employee_store es ON e.id = es.employee_id
             WHERE es.store_id = :store_id AND e.is_active = 1
             ORDER BY e.name'
        );
        $stmt->execute(['store_id' => $storeId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO employees (name, is_active) VALUES (:name, :is_active)');
        $stmt->execute([
            'name'      => $data['name'],
            'is_active' => $data['is_active'] ?? 1,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $fields = [];
        $params = ['id' => $id];

        foreach (['name', 'is_active', 'vacation_days_total'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $sql = 'UPDATE employees SET ' . implode(', ', $fields) . ' WHERE id = :id';
        return $db->prepare($sql)->execute($params);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('DELETE FROM employees WHERE id = :id')->execute(['id' => $id]);
    }

    public static function assignStores(int $employeeId, array $storeIds): void
    {
        $db = Database::getInstance();

        // Meglévő hozzárendelések törlése
        $db->prepare('DELETE FROM employee_store WHERE employee_id = :id')->execute(['id' => $employeeId]);

        // Újak beillesztése
        $stmt = $db->prepare('INSERT INTO employee_store (employee_id, store_id) VALUES (:emp, :store)');
        foreach ($storeIds as $storeId) {
            $stmt->execute(['emp' => $employeeId, 'store' => (int)$storeId]);
        }
    }

    public static function getStoreIds(int $employeeId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT store_id FROM employee_store WHERE employee_id = :id');
        $stmt->execute(['id' => $employeeId]);
        return array_column($stmt->fetchAll(), 'store_id');
    }

    public static function count(): int
    {
        $db = Database::getInstance();
        return (int)$db->query('SELECT COUNT(*) FROM employees WHERE is_active = 1')->fetchColumn();
    }

    public static function allActive(): array
    {
        $db = Database::getInstance();
        return $db->query('SELECT * FROM employees WHERE is_active = 1 ORDER BY name')->fetchAll();
    }
}
