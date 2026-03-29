<?php

namespace App\Models;

use App\Core\Database;

class Notification
{
    public static function getForUser(int $userId, int $limit = 50): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getUnreadCount(int $userId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function getUnread(int $userId, int $limit = 10): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM notifications WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function markAsRead(int $id): void
    {
        $db = Database::getInstance();
        $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id')->execute(['id' => $id]);
    }

    public static function markAllRead(int $userId): void
    {
        $db = Database::getInstance();
        $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id')->execute(['user_id' => $userId]);
    }

    public static function create(int $userId, string $type, string $title, ?string $message = null, ?string $link = null): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO notifications (user_id, type, title, message, link) VALUES (:user_id, :type, :title, :message, :link)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Értesítés küldése minden felhasználónak (kivéve a küldőt)
     */
    public static function notifyAll(string $type, string $title, ?string $message = null, ?string $link = null, ?int $exceptUserId = null): void
    {
        $db = Database::getInstance();
        $sql = 'SELECT id FROM users WHERE is_active = 1';
        $params = [];
        if ($exceptUserId) {
            $sql .= ' AND id != :except';
            $params['except'] = $exceptUserId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $userIds = array_column($stmt->fetchAll(), 'id');

        foreach ($userIds as $uid) {
            self::create((int)$uid, $type, $title, $message, $link);
        }
    }
}
