<?php

namespace App\Models;

use App\Core\Database;

class Supplier
{
    public static function all(): array
    {
        $db = Database::getInstance();
        return $db->query('SELECT * FROM suppliers ORDER BY name')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM suppliers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function search(string $query): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id, name FROM suppliers WHERE name LIKE :q ORDER BY name LIMIT 10');
        $stmt->execute(['q' => '%' . $query . '%']);
        return $stmt->fetchAll();
    }

    public static function findOrCreate(string $name): int
    {
        $db = Database::getInstance();
        $name = trim($name);

        $stmt = $db->prepare('SELECT id FROM suppliers WHERE name = :name');
        $stmt->execute(['name' => $name]);
        $existing = $stmt->fetch();

        if ($existing) {
            return (int)$existing['id'];
        }

        $db->prepare('INSERT INTO suppliers (name) VALUES (:name)')->execute(['name' => $name]);
        return (int)$db->lastInsertId();
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('DELETE FROM suppliers WHERE id = :id')->execute(['id' => $id]);
    }
}
