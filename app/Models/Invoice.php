<?php

namespace App\Models;

use App\Core\Database;

class Invoice
{
    public const PAYMENT_METHODS = [
        'keszpenz'  => 'Készpénz',
        'atutalas'  => 'Átutalás',
        'kartya'    => 'Bankkártya',
        'utanvet'   => 'Utánvét',
    ];

    public const CURRENCIES = [
        'HUF' => 'HUF (Ft)',
        'EUR' => 'EUR (€)',
    ];

    public static function all(?int $storeId = null, ?int $supplierId = null, ?string $isPaid = null): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT i.*, s.name as store_name, sp.name as supplier_name, u.name as recorded_by_name, b.name as bank_name
                FROM invoices i
                LEFT JOIN stores s ON i.store_id = s.id
                JOIN suppliers sp ON i.supplier_id = sp.id
                JOIN users u ON i.recorded_by = u.id
                LEFT JOIN banks b ON i.paid_from_bank_id = b.id
                WHERE 1=1';
        $params = [];

        if ($storeId) {
            $sql .= ' AND i.store_id = :store_id';
            $params['store_id'] = $storeId;
        }
        if ($supplierId) {
            $sql .= ' AND i.supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }
        if ($isPaid !== null && $isPaid !== '') {
            $sql .= ' AND i.is_paid = :is_paid';
            $params['is_paid'] = (int)$isPaid;
        }

        $sql .= ' ORDER BY i.invoice_date DESC, i.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT i.*, s.name as store_name, sp.name as supplier_name
             FROM invoices i
             LEFT JOIN stores s ON i.store_id = s.id
             JOIN suppliers sp ON i.supplier_id = sp.id
             WHERE i.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO invoices (store_id, supplier_id, invoice_number, net_amount, amount, currency, invoice_date, due_date, payment_method, notes, recorded_by)
             VALUES (:store_id, :supplier_id, :invoice_number, :net_amount, :amount, :currency, :invoice_date, :due_date, :payment_method, :notes, :recorded_by)'
        );
        $stmt->execute([
            'store_id'       => $data['store_id'],
            'supplier_id'    => $data['supplier_id'],
            'invoice_number' => $data['invoice_number'],
            'net_amount'     => $data['net_amount'] ?? $data['amount'],
            'amount'         => $data['amount'],
            'currency'       => $data['currency'] ?? 'HUF',
            'invoice_date'   => $data['invoice_date'],
            'due_date'       => $data['due_date'] ?: null,
            'payment_method' => $data['payment_method'],
            'notes'          => $data['notes'] ?: null,
            'recorded_by'    => $data['recorded_by'],
        ]);
        return (int)$db->lastInsertId();
    }

    public static function updateImage(int $id, string $path): bool
    {
        $db = Database::getInstance();
        return $db->prepare('UPDATE invoices SET image_path = :path WHERE id = :id')->execute(['path' => $path, 'id' => $id]);
    }

    public static function markPaid(int $id, ?int $bankId = null): bool
    {
        $db = Database::getInstance();
        return $db->prepare('UPDATE invoices SET is_paid = 1, paid_at = CURDATE(), paid_from_bank_id = :bank WHERE id = :id')
            ->execute(['bank' => $bankId, 'id' => $id]);
    }

    public static function markUnpaid(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('UPDATE invoices SET is_paid = 0, paid_at = NULL, paid_from_bank_id = NULL WHERE id = :id')->execute(['id' => $id]);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('DELETE FROM invoices WHERE id = :id')->execute(['id' => $id]);
    }

    public static function getOverdueCount(?int $storeId = null): int
    {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM invoices WHERE is_paid = 0 AND due_date < CURDATE()";
        $params = [];
        if ($storeId) {
            $sql .= ' AND store_id = :store_id';
            $params['store_id'] = $storeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
