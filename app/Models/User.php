<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function all(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            'SELECT u.*, s.name as store_name
             FROM users u
             LEFT JOIN stores s ON u.store_id = s.id
             ORDER BY u.role ASC, u.name ASC'
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT u.*, s.name as store_name
             FROM users u
             LEFT JOIN stores s ON u.store_id = s.id
             WHERE u.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password, role, store_id, is_active)
             VALUES (:name, :email, :password, :role, :store_id, :is_active)'
        );
        $stmt->execute([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'role'      => $data['role'],
            'store_id'  => $data['store_id'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();

        $fields = [];
        $params = ['id' => $id];

        foreach (['name', 'email', 'role', 'store_id', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        // Jelszó csak ha meg van adva
        if (!empty($data['password'])) {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function updateRememberToken(int $id, ?string $token): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET remember_token = :token WHERE id = :id');
        $stmt->execute(['token' => $token, 'id' => $id]);
    }

    public static function count(): int
    {
        $db = Database::getInstance();
        return (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function getByRole(string $role): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE role = :role AND is_active = 1 ORDER BY name');
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll();
    }
}
