<?php

namespace App\Models;

use App\Core\Database;

class CompanySetting
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT setting_value FROM company_settings WHERE setting_key = :key');
        $stmt->execute(['key' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO company_settings (setting_key, setting_value) VALUES (:key, :val)
             ON DUPLICATE KEY UPDATE setting_value = :val2'
        );
        $stmt->execute(['key' => $key, 'val' => $value, 'val2' => $value]);
    }

    public static function getAll(): array
    {
        $db = Database::getInstance();
        $rows = $db->query('SELECT setting_key, setting_value FROM company_settings')->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $result[$r['setting_key']] = $r['setting_value'];
        }
        return $result;
    }
}
