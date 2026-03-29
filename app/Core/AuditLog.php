<?php

namespace App\Core;

class AuditLog
{
    /**
     * Módosítás naplózása
     */
    public static function log(
        string $action,
        string $tableName,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $db = Database::getInstance();

        $stmt = $db->prepare(
            'INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address)
             VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address)'
        );

        $stmt->execute([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'table_name' => $tableName,
            'record_id'  => $recordId,
            'old_values'  => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values'  => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    /**
     * Audit log lekérdezése (tulajdonos számára)
     */
    public static function getAll(int $limit = 50, int $offset = 0, ?string $tableName = null): array
    {
        $db = Database::getInstance();

        $sql = 'SELECT a.*, u.name as user_name
                FROM audit_log a
                LEFT JOIN users u ON a.user_id = u.id';
        $params = [];

        if ($tableName) {
            $sql .= ' WHERE a.table_name = :table_name';
            $params['table_name'] = $tableName;
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Adott rekord története
     */
    public static function getForRecord(string $tableName, int $recordId): array
    {
        $db = Database::getInstance();

        $stmt = $db->prepare(
            'SELECT a.*, u.name as user_name
             FROM audit_log a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.table_name = :table_name AND a.record_id = :record_id
             ORDER BY a.created_at DESC'
        );
        $stmt->execute([
            'table_name' => $tableName,
            'record_id'  => $recordId,
        ]);

        return $stmt->fetchAll();
    }
}
