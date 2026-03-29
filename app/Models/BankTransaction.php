<?php

namespace App\Models;

use App\Core\Database;

class BankTransaction
{
    public const TYPES = [
        'kartya_beerkezes' => 'Kártyás forgalom beérkezés',
        'szolgaltato_levon' => 'Szolgáltatói levonás',
        'hitel_torlesztes' => 'Hitel törlesztő részlet',
        'szamla_kozti'     => 'Számlák közötti átutalás',
    ];

    /**
     * Összes tranzakció szűrőkkel
     */
    public static function all(?int $bankId = null, ?string $type = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT bt.*, b.name as bank_name, b.currency as bank_currency,
                       u.name as recorded_by_name,
                       i.invoice_number, sp.name as invoice_supplier_name,
                       lb.name as loan_name,
                       tb.name as target_bank_name, tb.currency as target_bank_currency
                FROM bank_transactions bt
                JOIN banks b ON bt.bank_id = b.id
                JOIN users u ON bt.recorded_by = u.id
                LEFT JOIN invoices i ON bt.invoice_id = i.id
                LEFT JOIN suppliers sp ON i.supplier_id = sp.id
                LEFT JOIN banks lb ON bt.loan_bank_id = lb.id
                LEFT JOIN banks tb ON bt.target_bank_id = tb.id
                WHERE 1=1';
        $params = [];

        if ($bankId) {
            $sql .= ' AND bt.bank_id = :bank_id';
            $params['bank_id'] = $bankId;
        }
        if ($type) {
            $sql .= ' AND bt.type = :type';
            $params['type'] = $type;
        }
        if ($dateFrom) {
            $sql .= ' AND bt.transaction_date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= ' AND bt.transaction_date <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $sql .= ' ORDER BY bt.transaction_date DESC, bt.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Boltok hozzárendelése kártyás beérkezéshez
        foreach ($results as &$row) {
            if ($row['type'] === 'kartya_beerkezes') {
                $row['stores'] = self::getStores($row['id']);
                $row['gross_amount'] = self::calculateGross($row['id']);
                $row['commission'] = $row['gross_amount'] - (float)$row['amount'];
            }
        }

        return $results;
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT bt.*, b.name as bank_name
             FROM bank_transactions bt
             JOIN banks b ON bt.bank_id = b.id
             WHERE bt.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        if ($row['type'] === 'kartya_beerkezes') {
            $row['stores'] = self::getStores($row['id']);
            $row['store_ids'] = array_column($row['stores'], 'store_id');
            $row['gross_amount'] = self::calculateGross($row['id']);
            $row['commission'] = $row['gross_amount'] - (float)$row['amount'];
        }

        return $row;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO bank_transactions (bank_id, type, amount, source_amount, target_currency, transaction_date, date_from, date_to, provider_name, invoice_id, loan_bank_id, target_bank_id, notes, recorded_by)
             VALUES (:bank_id, :type, :amount, :source_amount, :target_currency, :transaction_date, :date_from, :date_to, :provider_name, :invoice_id, :loan_bank_id, :target_bank_id, :notes, :recorded_by)'
        );
        $stmt->execute([
            'bank_id'          => $data['bank_id'],
            'type'             => $data['type'],
            'amount'           => $data['amount'],
            'source_amount'    => $data['source_amount'] ?? null,
            'target_currency'  => $data['target_currency'] ?? null,
            'transaction_date' => $data['transaction_date'],
            'date_from'        => $data['date_from'] ?? null,
            'date_to'          => $data['date_to'] ?? null,
            'provider_name'    => $data['provider_name'] ?? null,
            'invoice_id'       => $data['invoice_id'] ?? null,
            'loan_bank_id'     => $data['loan_bank_id'] ?? null,
            'target_bank_id'   => $data['target_bank_id'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'recorded_by'      => $data['recorded_by'],
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Boltok hozzárendelése kártyás beérkezéshez
     */
    public static function assignStores(int $transactionId, array $storeIds): void
    {
        $db = Database::getInstance();
        $db->prepare('DELETE FROM bank_transaction_stores WHERE bank_transaction_id = :id')
           ->execute(['id' => $transactionId]);

        $stmt = $db->prepare('INSERT INTO bank_transaction_stores (bank_transaction_id, store_id) VALUES (:tid, :sid)');
        foreach ($storeIds as $sid) {
            $stmt->execute(['tid' => $transactionId, 'sid' => (int)$sid]);
        }
    }

    /**
     * Tranzakcióhoz rendelt boltok
     */
    public static function getStores(int $transactionId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT bts.store_id, s.name as store_name
             FROM bank_transaction_stores bts
             JOIN stores s ON bts.store_id = s.id
             WHERE bts.bank_transaction_id = :id'
        );
        $stmt->execute(['id' => $transactionId]);
        return $stmt->fetchAll();
    }

    /**
     * Bruttó összeg: a boltok napi bankkártya forgalma a megadott időszakra
     */
    public static function calculateGross(int $transactionId): float
    {
        $db = Database::getInstance();

        // Tranzakció adatai
        $stmt = $db->prepare('SELECT date_from, date_to FROM bank_transactions WHERE id = :id');
        $stmt->execute(['id' => $transactionId]);
        $tx = $stmt->fetch();
        if (!$tx || !$tx['date_from'] || !$tx['date_to']) return 0;

        // Boltok
        $storeIds = array_column(self::getStores($transactionId), 'store_id');
        if (empty($storeIds)) return 0;

        $placeholders = implode(',', array_fill(0, count($storeIds), '?'));
        $params = $storeIds;
        $params[] = $tx['date_from'];
        $params[] = $tx['date_to'];

        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM financial_records
             WHERE store_id IN ({$placeholders})
             AND purpose = 'napi_bankkartya'
             AND record_date >= ? AND record_date <= ?"
        );
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Szolgáltatói levonás összekötése számlával
     */
    public static function linkInvoice(int $transactionId, ?int $invoiceId): bool
    {
        $db = Database::getInstance();
        return $db->prepare('UPDATE bank_transactions SET invoice_id = :inv WHERE id = :id')
            ->execute(['inv' => $invoiceId, 'id' => $transactionId]);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $db->prepare('DELETE FROM bank_transaction_stores WHERE bank_transaction_id = :id')->execute(['id' => $id]);
        return $db->prepare('DELETE FROM bank_transactions WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Nincs-e még rögzítve kártyás beérkezés az adott időszakra + bankra
     */
    public static function hasCardIncomeForPeriod(int $bankId, string $dateFrom, string $dateTo): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM bank_transactions
             WHERE bank_id = :bank_id AND type = 'kartya_beerkezes'
             AND date_from = :df AND date_to = :dt"
        );
        $stmt->execute(['bank_id' => $bankId, 'df' => $dateFrom, 'dt' => $dateTo]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Összesítés bankszámla egyenleghez
     */
    public static function sumByBank(int $bankId, string $type): float
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :bank_id AND type = :type');
        $stmt->execute(['bank_id' => $bankId, 'type' => $type]);
        return (float)$stmt->fetchColumn();
    }
}
