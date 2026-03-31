<?php

namespace App\Models;

use App\Core\Database;

class OwnerPayment
{
    public const OWNERS = [
        'Imi'  => 'Imi',
        'Ádám' => 'Ádám',
    ];

    public const SOURCES = [
        'bank'        => 'Bank',
        'vorosmarty'  => 'Vörösmarty',
        'selmeci'     => 'Selmeci',
        'ulloi_ut'    => 'Üllői út',
        'egyeb'       => 'Egyéb',
    ];

    public static function all(?string $ownerName = null, ?int $year = null, ?int $month = null): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT * FROM owner_payments WHERE 1=1';
        $params = [];

        if ($ownerName) {
            $sql .= ' AND owner_name = :owner_name';
            $params['owner_name'] = $ownerName;
        }
        if ($year) {
            $sql .= ' AND year = :year';
            $params['year'] = $year;
        }
        if ($month) {
            $sql .= ' AND month = :month';
            $params['month'] = $month;
        }

        $sql .= ' ORDER BY year DESC, month DESC, created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO owner_payments (owner_name, month, year, payment_source, amount, recorded_by)
             VALUES (:owner_name, :month, :year, :source, :amount, :recorded_by)'
        );
        $stmt->execute([
            'owner_name'  => $data['owner_name'],
            'month'       => $data['month'],
            'year'        => $data['year'],
            'source'      => $data['source'],
            'amount'      => $data['amount'],
            'recorded_by' => $data['recorded_by'],
        ]);
        return (int)$db->lastInsertId();
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('DELETE FROM owner_payments WHERE id = :id')->execute(['id' => $id]);
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM owner_payments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function linkBankTransaction(int $paymentId, int $bankTransactionId): void
    {
        $db = Database::getInstance();
        $db->prepare('UPDATE owner_payments SET bank_transaction_id = :tx_id WHERE id = :id')
           ->execute(['tx_id' => $bankTransactionId, 'id' => $paymentId]);
    }
}
