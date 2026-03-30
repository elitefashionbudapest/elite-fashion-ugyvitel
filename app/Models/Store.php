<?php

namespace App\Models;

use App\Core\Database;

class Store
{
    public static function all(): array
    {
        $db = Database::getInstance();
        return $db->query('SELECT * FROM stores ORDER BY name')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM stores WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO stores (name, open_days) VALUES (:name, :open_days)');
        $stmt->execute(['name' => $data['name'], 'open_days' => $data['open_days'] ?? '1,2,3,4,5,6']);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE stores SET name = :name, open_days = :open_days WHERE id = :id');
        return $stmt->execute(['name' => $data['name'], 'open_days' => $data['open_days'] ?? '1,2,3,4,5,6', 'id' => $id]);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM stores WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function count(): int
    {
        $db = Database::getInstance();
        return (int)$db->query('SELECT COUNT(*) FROM stores')->fetchColumn();
    }
}
