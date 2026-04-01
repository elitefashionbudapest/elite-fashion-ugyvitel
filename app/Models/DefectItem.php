<?php

namespace App\Models;

use App\Core\Database;

class DefectItem
{
    /**
     * Selejt tetelek lekeerdezese szurokkel
     */
    public static function all(?int $storeId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT d.*, s.name as store_name, u.name as scanned_by_name
                FROM defect_items d
                JOIN stores s ON d.store_id = s.id
                JOIN users u ON d.scanned_by = u.id
                WHERE 1=1';
        $params = [];

        if ($storeId) {
            $sql .= ' AND d.store_id = :store_id';
            $params['store_id'] = $storeId;
        }
        if ($dateFrom) {
            $sql .= ' AND DATE(d.scanned_at) >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= ' AND DATE(d.scanned_at) <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $sql .= ' ORDER BY d.scanned_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Uj selejt tetel letrehozasa
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO defect_items (store_id, barcode, product_name, product_price, scanned_by, scanned_at)
             VALUES (:store_id, :barcode, :product_name, :product_price, :scanned_by, NOW())'
        );
        $stmt->execute([
            'store_id'      => $data['store_id'],
            'barcode'       => $data['barcode'],
            'product_name'  => $data['product_name'] ?? null,
            'product_price' => $data['product_price'] ?? null,
            'scanned_by'    => $data['scanned_by'],
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Selejt tetel keresese ID alapjan
     */
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT d.*, s.name as store_name, u.name as scanned_by_name
             FROM defect_items d
             JOIN stores s ON d.store_id = s.id
             JOIN users u ON d.scanned_by = u.id
             WHERE d.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Selejt tetel torlese
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('DELETE FROM defect_items WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Exporthoz: ugyanaz mint all(), CSV/Excel generalashoz
     */
    public static function getForExport(?int $storeId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return self::all($storeId, $dateFrom, $dateTo);
    }
}
