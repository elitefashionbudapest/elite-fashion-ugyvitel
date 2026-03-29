<?php

namespace App\Models;

use App\Core\Database;

class TabPermission
{
    public const TABS = [
        'dashboard'      => ['icon' => 'fa-gauge-high',       'label' => 'Kezdőlap'],
        'konyveles'      => ['icon' => 'fa-building-columns',  'label' => 'Könyvelés'],
        'fizetes'        => ['icon' => 'fa-money-bill-wave',   'label' => 'Fizetések'],
        'ertekeles'      => ['icon' => 'fa-star',              'label' => 'Értékelések'],
        'szabadsag'      => ['icon' => 'fa-umbrella-beach',    'label' => 'Szabadság'],
        'beosztas'       => ['icon' => 'fa-calendar-days',     'label' => 'Beosztás'],
        'szamlak'        => ['icon' => 'fa-file-invoice',      'label' => 'Számlák'],
        'selejt'         => ['icon' => 'fa-barcode',           'label' => 'Selejt'],
        'chat'           => ['icon' => 'fa-comments',          'label' => 'Chat'],
        'kimutat'        => ['icon' => 'fa-chart-line',        'label' => 'Kimutatások'],
        'konyvelo_docs'  => ['icon' => 'fa-folder-open',      'label' => 'Könyvelői dok.'],
    ];

    public static function getPermission(int $userId, string $tabSlug): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM tab_permissions WHERE user_id = :user_id AND tab_slug = :tab_slug');
        $stmt->execute(['user_id' => $userId, 'tab_slug' => $tabSlug]);
        return $stmt->fetch() ?: null;
    }

    public static function getAllForUser(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM tab_permissions WHERE user_id = :user_id ORDER BY tab_slug');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function save(int $userId, string $tabSlug, bool $canView, bool $canCreate, bool $canEdit): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO tab_permissions (user_id, tab_slug, can_view, can_create, can_edit)
             VALUES (:user_id, :tab_slug, :can_view, :can_create, :can_edit)
             ON DUPLICATE KEY UPDATE can_view = :can_view2, can_create = :can_create2, can_edit = :can_edit2'
        );
        $stmt->execute([
            'user_id'     => $userId,
            'tab_slug'    => $tabSlug,
            'can_view'    => (int)$canView,
            'can_create'  => (int)$canCreate,
            'can_edit'    => (int)$canEdit,
            'can_view2'   => (int)$canView,
            'can_create2' => (int)$canCreate,
            'can_edit2'   => (int)$canEdit,
        ]);
    }

    public static function hasAnyPermissions(int $userId): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT COUNT(*) FROM tab_permissions WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function getVisibleTabs(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT tab_slug FROM tab_permissions WHERE user_id = :user_id AND can_view = 1');
        $stmt->execute(['user_id' => $userId]);
        return array_column($stmt->fetchAll(), 'tab_slug');
    }

    public static function canCreate(int $userId, string $tabSlug): bool
    {
        $perm = self::getPermission($userId, $tabSlug);
        return $perm && $perm['can_create'];
    }

    public static function canEdit(int $userId, string $tabSlug): bool
    {
        $perm = self::getPermission($userId, $tabSlug);
        return $perm && $perm['can_edit'];
    }
}
