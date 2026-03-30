<?php

namespace App\Models;

use App\Core\Database;

class FinancialRecord
{
    public const PURPOSES = [
        'napi_keszpenz'    => 'Napi KÉSZPÉNZ forgalom',
        'napi_bankkartya'  => 'Napi BANKKÁRTYA forgalom',
        'meretre_igazitas' => 'Méretre igazítás kifizetés',
        'tankolas'         => 'Tankolás',
        'munkaber'         => 'Munkabér kifizetés',
        'egyeb_kifizetes'  => 'Egyéb kifizetés',
        'bank_kifizetes'   => 'Befizetés bankba',
        'befizetes_bankbol'=> 'Befizetés bankból',
        'befizetes_boltbol'=> 'Befizetés másik boltból',
        'kassza_nyito'     => 'Kassza NYITÓ összeg',
        'szamla_kifizetes' => 'Bejövő számla kifizetés',
        'selejt_befizetes' => 'Selejt befizetés',
    ];

    public static function allMultiStore(?array $storeIds = null, ?string $dateFrom = null, ?string $dateTo = null, ?string $purpose = null): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT f.*, s.name as store_name, u.name as recorded_by_name, e.name as paid_to_name, b.name as bank_name
                FROM financial_records f
                JOIN stores s ON f.store_id = s.id
                JOIN users u ON f.recorded_by = u.id
                LEFT JOIN employees e ON f.paid_to_employee_id = e.id
                LEFT JOIN banks b ON f.bank_id = b.id
                WHERE 1=1';
        $params = [];

        if (!empty($storeIds)) {
            $placeholders = implode(',', array_fill(0, count($storeIds), '?'));
            $sql .= " AND f.store_id IN ({$placeholders})";
            $params = array_map('intval', $storeIds);
        }
        if ($dateFrom) { $sql .= ' AND f.record_date >= ?'; $params[] = $dateFrom; }
        if ($dateTo) { $sql .= ' AND f.record_date <= ?'; $params[] = $dateTo; }
        if ($purpose) { $sql .= ' AND f.purpose = ?'; $params[] = $purpose; }

        $sql .= ' ORDER BY f.record_date DESC, f.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function all(?int $storeId = null, ?string $dateFrom = null, ?string $dateTo = null, ?string $purpose = null): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT f.*, s.name as store_name, u.name as recorded_by_name, e.name as paid_to_name, b.name as bank_name
                FROM financial_records f
                JOIN stores s ON f.store_id = s.id
                JOIN users u ON f.recorded_by = u.id
                LEFT JOIN employees e ON f.paid_to_employee_id = e.id
                LEFT JOIN banks b ON f.bank_id = b.id
                WHERE 1=1';
        $params = [];

        if ($storeId) {
            $sql .= ' AND f.store_id = :store_id';
            $params['store_id'] = $storeId;
        }
        if ($dateFrom) {
            $sql .= ' AND f.record_date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= ' AND f.record_date <= :date_to';
            $params['date_to'] = $dateTo;
        }
        if ($purpose) {
            $sql .= ' AND f.purpose = :purpose';
            $params['purpose'] = $purpose;
        }

        $sql .= ' ORDER BY f.record_date DESC, f.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT f.*, s.name as store_name FROM financial_records f
             JOIN stores s ON f.store_id = s.id WHERE f.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO financial_records (store_id, recorded_by, record_date, purpose, amount, description, paid_to_employee_id, bank_id)
             VALUES (:store_id, :recorded_by, :record_date, :purpose, :amount, :description, :paid_to, :bank_id)'
        );
        $stmt->execute([
            'store_id'    => $data['store_id'],
            'recorded_by' => $data['recorded_by'],
            'record_date' => $data['record_date'],
            'purpose'     => $data['purpose'],
            'amount'      => $data['amount'],
            'description' => $data['description'] ?? null,
            'paid_to'     => $data['paid_to_employee_id'] ?? null,
            'bank_id'     => $data['bank_id'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'UPDATE financial_records SET store_id = :store_id, record_date = :record_date,
             purpose = :purpose, amount = :amount, description = :description,
             paid_to_employee_id = :paid_to, bank_id = :bank_id WHERE id = :id'
        );
        return $stmt->execute([
            'store_id'    => $data['store_id'],
            'record_date' => $data['record_date'],
            'purpose'     => $data['purpose'],
            'amount'      => $data['amount'],
            'description' => $data['description'] ?? null,
            'paid_to'     => $data['paid_to_employee_id'] ?? null,
            'bank_id'     => $data['bank_id'] ?? null,
            'id'          => $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('DELETE FROM financial_records WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Pénzügyi összesítő boltonként
     */
    public static function summaryByStore(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $db = Database::getInstance();
        $sql = "SELECT s.id, s.name,
                COALESCE(SUM(CASE WHEN f.purpose = 'napi_keszpenz' THEN f.amount ELSE 0 END), 0) as keszpenz,
                COALESCE(SUM(CASE WHEN f.purpose = 'napi_bankkartya' THEN f.amount ELSE 0 END), 0) as bankkartya,
                COALESCE(SUM(CASE WHEN f.purpose IN ('befizetes_bankbol','befizetes_boltbol') THEN f.amount ELSE 0 END), 0) as befizetes,
                COALESCE(SUM(CASE WHEN f.purpose = 'befizetes_bankbol' THEN f.amount ELSE 0 END), 0) as befizetes_bankbol,
                COALESCE(SUM(CASE WHEN f.purpose = 'befizetes_boltbol' THEN f.amount ELSE 0 END), 0) as befizetes_boltbol,
                COALESCE(SUM(CASE WHEN f.purpose = 'selejt_befizetes' THEN f.amount ELSE 0 END), 0) as selejt_befizetes,
                COALESCE(SUM(CASE WHEN f.purpose = 'kassza_nyito' THEN f.amount ELSE 0 END), 0) as kassza_nyito,
                COALESCE(SUM(CASE WHEN f.purpose = 'munkaber' THEN f.amount ELSE 0 END), 0) as munkaber,
                COALESCE(SUM(CASE WHEN f.purpose = 'bank_kifizetes' THEN f.amount ELSE 0 END), 0) as bank_kifizetes,
                COALESCE(SUM(CASE WHEN f.purpose = 'szamla_kifizetes' THEN f.amount ELSE 0 END), 0) as szamla_kifizetes,
                COALESCE(SUM(CASE WHEN f.purpose IN ('meretre_igazitas','tankolas','egyeb_kifizetes') THEN f.amount ELSE 0 END), 0) as egyeb_kiadasok,
                COALESCE(SUM(CASE WHEN f.purpose IN ('meretre_igazitas','tankolas','munkaber','egyeb_kifizetes','bank_kifizetes','szamla_kifizetes') THEN f.amount ELSE 0 END), 0) as kiadasok,
                COALESCE(SUM(f.amount), 0) as total
                FROM stores s
                LEFT JOIN financial_records f ON s.id = f.store_id";

        $params = [];
        $where = [];

        if ($dateFrom) {
            $where[] = 'f.record_date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $where[] = 'f.record_date <= :date_to';
            $params['date_to'] = $dateTo;
        }

        if (!empty($where)) {
            $sql .= ' AND ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY s.id, s.name ORDER BY s.name';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Selejt összértékek boltonként
        $selejtSql = "SELECT store_id, COALESCE(SUM(total_value), 0) as selejt_osszeg
                      FROM defect_daily_values WHERE 1=1";
        $selejtParams = [];
        if ($dateFrom) {
            $selejtSql .= ' AND value_date >= :date_from';
            $selejtParams['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $selejtSql .= ' AND value_date <= :date_to';
            $selejtParams['date_to'] = $dateTo;
        }
        $selejtSql .= ' GROUP BY store_id';
        $stmt = $db->prepare($selejtSql);
        $stmt->execute($selejtParams);
        $selejtByStore = array_column($stmt->fetchAll(), 'selejt_osszeg', 'store_id');

        foreach ($results as &$row) {
            $row['selejt'] = (float)($selejtByStore[$row['id']] ?? 0);
        }

        return $results;
    }
}
