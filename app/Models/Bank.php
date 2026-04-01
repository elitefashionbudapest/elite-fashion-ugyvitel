<?php

namespace App\Models;

use App\Core\Database;

class Bank
{
    /** Aktív bankszámlák (nem hitelek) */
    public static function all(): array
    {
        $db = Database::getInstance();
        return $db->query('SELECT * FROM banks WHERE is_active = 1 AND is_loan = 0 ORDER BY name')->fetchAll();
    }

    /** Aktív hitelek */
    public static function allLoans(): array
    {
        $db = Database::getInstance();
        return $db->query('SELECT * FROM banks WHERE is_active = 1 AND is_loan = 1 ORDER BY name')->fetchAll();
    }

    /** Minden (szerkesztéshez) */
    public static function allWithInactive(): array
    {
        $db = Database::getInstance();
        return $db->query('SELECT * FROM banks ORDER BY is_loan, name')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM banks WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO banks (name, currency, account_number, notes, opening_balance, min_balance, is_loan) VALUES (:name, :currency, :account_number, :notes, :opening_balance, :min_balance, :is_loan)');
        $stmt->execute([
            'name'            => $data['name'],
            'currency'        => $data['currency'] ?? 'HUF',
            'account_number'  => $data['account_number'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'min_balance'     => $data['min_balance'] ?? null,
            'is_loan'         => $data['is_loan'] ?? 0,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE banks SET name = :name, currency = :currency, account_number = :account_number, notes = :notes, opening_balance = :opening_balance, min_balance = :min_balance, is_loan = :is_loan, is_active = :is_active WHERE id = :id');
        return $stmt->execute([
            'name'            => $data['name'],
            'currency'        => $data['currency'] ?? 'HUF',
            'account_number'  => $data['account_number'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'min_balance'     => $data['min_balance'] ?? null,
            'is_loan'         => $data['is_loan'] ?? 0,
            'is_active'       => $data['is_active'] ?? 1,
            'id'              => $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare('DELETE FROM banks WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Bank/hitel egyenleg számítás
     */
    public static function getBalance(int $bankId): float
    {
        $db = Database::getInstance();
        $bank = self::find($bankId);
        if (!$bank) return 0;

        $balance = (float)$bank['opening_balance'];

        if ($bank['is_loan']) {
            // HITEL: nyitó (negatív, a tartozás) + törlesztések (csökkenti a tartozást)
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE loan_bank_id = :id AND type = 'hitel_torlesztes'");
            $stmt->execute(['id' => $bankId]);
            $balance += (float)$stmt->fetchColumn();
        } else {
            // BANKSZÁMLA
            // + Befizetés bankba (boltból)
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_records WHERE bank_id = :bank_id AND purpose = 'bank_kifizetes'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance += (float)$stmt->fetchColumn();

            // + Kártyás forgalom beérkezés
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :bank_id AND type = 'kartya_beerkezes'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance += (float)$stmt->fetchColumn();

            // - Kivétel bankból boltba
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_records WHERE bank_id = :bank_id AND purpose = 'befizetes_bankbol'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance -= (float)$stmt->fetchColumn();

            // - Számlák kifizetése ebből a bankból (kivéve ami már bank tranzakcióhoz van kötve)
            $stmt = $db->prepare(
                "SELECT COALESCE(SUM(i.amount), 0) FROM invoices i
                 WHERE i.paid_from_bank_id = :bank_id AND i.is_paid = 1
                 AND NOT EXISTS (SELECT 1 FROM bank_transactions bt WHERE bt.invoice_id = i.id)"
            );
            $stmt->execute(['bank_id' => $bankId]);
            $balance -= (float)$stmt->fetchColumn();

            // - Szolgáltatói levonások
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :bank_id AND type = 'szolgaltato_levon'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance -= (float)$stmt->fetchColumn();

            // - Hitel törlesztések (innen megy ki a pénz)
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :bank_id AND type = 'hitel_torlesztes'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance -= (float)$stmt->fetchColumn();

            // - Számlák közötti átutalás KIMENŐ (source_amount a küldő pénznemében)
            $stmt = $db->prepare("SELECT COALESCE(SUM(source_amount), 0) FROM bank_transactions WHERE bank_id = :bank_id AND type = 'szamla_kozti'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance -= (float)$stmt->fetchColumn();

            // + Számlák közötti átutalás BEJÖVŐ (amount a fogadó pénznemében)
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE target_bank_id = :bank_id AND type = 'szamla_kozti'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance += (float)$stmt->fetchColumn();

            // - Banki jutalékok
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :bank_id AND type = 'banki_jutalek'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance -= (float)$stmt->fetchColumn();

            // - Tulajdonosi fizetések (bankból)
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :bank_id AND type = 'tulajdonosi_fizetes'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance -= (float)$stmt->fetchColumn();

            // - Adó kifizetések
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :bank_id AND type = 'ado_kifizetes'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance -= (float)$stmt->fetchColumn();

            // + Tagi kölcsön befizetés
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :bank_id AND type = 'tagi_kolcson_be'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance += (float)$stmt->fetchColumn();

            // - Tagi kölcsön visszafizetés
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE bank_id = :bank_id AND type = 'tagi_kolcson_ki'");
            $stmt->execute(['bank_id' => $bankId]);
            $balance -= (float)$stmt->fetchColumn();
        }

        return $balance;
    }

    /** Összes bank+hitel egyenleggel (+ HUF átszámítás devizáknál) */
    public static function allWithBalance(): array
    {
        $banks = self::allWithInactive();
        foreach ($banks as &$bank) {
            $bank['balance'] = self::getBalance($bank['id']);
            $bank['balance_huf'] = \App\Core\ExchangeRate::toHuf($bank['balance'], $bank['currency']);
        }
        return $banks;
    }
}
