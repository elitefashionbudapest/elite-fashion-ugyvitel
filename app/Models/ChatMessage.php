<?php

namespace App\Models;

use App\Core\Database;

class ChatMessage
{
    /**
     * Publikus (kozos) chat uzenetek lekerese
     */
    public static function getPublicMessages(int $limit = 50, ?int $beforeId = null): array
    {
        $db = Database::getInstance();

        $sql = 'SELECT cm.*, u.name as sender_name
                FROM chat_messages cm
                JOIN users u ON cm.sender_id = u.id
                WHERE cm.receiver_id IS NULL';

        $params = [];

        if ($beforeId !== null) {
            $sql .= ' AND cm.id < :before_id';
            $params['before_id'] = $beforeId;
        }

        $sql .= ' ORDER BY cm.created_at DESC LIMIT :limit';

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Privat uzenetek ket felhasznalo kozott
     */
    public static function getPrivateMessages(int $userId1, int $userId2, int $limit = 50, ?int $beforeId = null): array
    {
        $db = Database::getInstance();

        $sql = 'SELECT cm.*, u.name as sender_name
                FROM chat_messages cm
                JOIN users u ON cm.sender_id = u.id
                WHERE (
                    (cm.sender_id = :uid1a AND cm.receiver_id = :uid2a)
                    OR
                    (cm.sender_id = :uid2b AND cm.receiver_id = :uid1b)
                )';

        $params = [
            'uid1a' => $userId1,
            'uid2a' => $userId2,
            'uid2b' => $userId2,
            'uid1b' => $userId1,
        ];

        if ($beforeId !== null) {
            $sql .= ' AND cm.id < :before_id';
            $params['before_id'] = $beforeId;
        }

        $sql .= ' ORDER BY cm.created_at DESC LIMIT :limit';

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Felhasznalo beszelgeteseinek listaja (privat)
     * Visszaadja az osszes felhasznalot akivel mar valtott uzenetet,
     * az utolso uzenet elonetevel es olvasatlan szamlaloval.
     */
    public static function getConversations(int $userId): array
    {
        $db = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT
                u.id as user_id,
                u.name as user_name,
                u.role,
                (
                    SELECT cm2.message
                    FROM chat_messages cm2
                    WHERE (cm2.sender_id = u.id AND cm2.receiver_id = :uid1)
                       OR (cm2.sender_id = :uid2 AND cm2.receiver_id = u.id)
                    ORDER BY cm2.created_at DESC
                    LIMIT 1
                ) as last_message,
                (
                    SELECT cm3.created_at
                    FROM chat_messages cm3
                    WHERE (cm3.sender_id = u.id AND cm3.receiver_id = :uid3)
                       OR (cm3.sender_id = :uid4 AND cm3.receiver_id = u.id)
                    ORDER BY cm3.created_at DESC
                    LIMIT 1
                ) as last_message_at,
                (
                    SELECT COUNT(*)
                    FROM chat_messages cm4
                    WHERE cm4.sender_id = u.id
                      AND cm4.receiver_id = :uid5
                      AND cm4.is_read = 0
                ) as unread_count
            FROM users u
            WHERE u.id != :uid6
              AND u.is_active = 1
            ORDER BY last_message_at DESC, u.name ASC"
        );

        $stmt->execute([
            'uid1' => $userId,
            'uid2' => $userId,
            'uid3' => $userId,
            'uid4' => $userId,
            'uid5' => $userId,
            'uid6' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Uzenet kuldese
     * $receiverId = null eseten publikus uzenet
     */
    public static function send(int $senderId, ?int $receiverId, string $message): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO chat_messages (sender_id, receiver_id, message, is_read, created_at)
             VALUES (:sender_id, :receiver_id, :message, 0, NOW())'
        );
        $stmt->execute([
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'message'     => $message,
        ]);

        return (int) $db->lastInsertId();
    }

    /**
     * Uzenetek olvasottra allitasa
     * Ha $senderId megadva: csak az adott felhasznalotol erkezo uzeneteket jeloli olvasottnak
     * Ha $senderId null: publikus uzenetek olvasottra allitasa nem szukseges (nincs is_read publikusra)
     */
    public static function markAsRead(int $userId, ?int $senderId = null): void
    {
        $db = Database::getInstance();

        if ($senderId !== null) {
            // Privat uzenetek olvasottra allitasa: az adott felhasznalotol erkezo uzenetek
            $stmt = $db->prepare(
                'UPDATE chat_messages
                 SET is_read = 1
                 WHERE sender_id = :sender_id
                   AND receiver_id = :user_id
                   AND is_read = 0'
            );
            $stmt->execute([
                'sender_id' => $senderId,
                'user_id'   => $userId,
            ]);
        }
    }

    /**
     * Osszes olvasatlan uzenet szama egy felhasznalonak
     */
    public static function getUnreadCount(int $userId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT COUNT(*)
             FROM chat_messages
             WHERE receiver_id = :user_id
               AND is_read = 0'
        );
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }
}
