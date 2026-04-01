<?php

namespace App\Models;

use App\Core\Database;

class Product
{
    /**
     * Termék keresése vonalkód alapján
     */
    public static function findByBarcode(string $barcode): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM products WHERE barcode = :barcode');
        $stmt->execute(['barcode' => $barcode]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Összes termék lekérdezése
     */
    public static function all(): array
    {
        $db = Database::getInstance();
        return $db->query('SELECT * FROM products ORDER BY name ASC')->fetchAll();
    }

    /**
     * Termékek száma
     */
    public static function count(): int
    {
        $db = Database::getInstance();
        return (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    /**
     * Összes termék törlése (újra-importálás előtt)
     */
    public static function truncate(): void
    {
        $db = Database::getInstance();
        $db->exec('DELETE FROM products');
    }

    /**
     * Termék beszúrása vagy frissítése vonalkód alapján
     */
    public static function upsert(array $data): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO products (name, sku, barcode, product_type, net_price, vat_rate, gross_price)
             VALUES (:name, :sku, :barcode, :product_type, :net_price, :vat_rate, :gross_price)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                sku = VALUES(sku),
                product_type = VALUES(product_type),
                net_price = VALUES(net_price),
                vat_rate = VALUES(vat_rate),
                gross_price = VALUES(gross_price)'
        );
        $stmt->execute([
            'name'         => $data['name'],
            'sku'          => $data['sku'] ?? null,
            'barcode'      => $data['barcode'],
            'product_type' => $data['product_type'] ?? null,
            'net_price'    => $data['net_price'] ?? 0,
            'vat_rate'     => $data['vat_rate'] ?? null,
            'gross_price'  => $data['gross_price'] ?? 0,
        ]);
    }
}
