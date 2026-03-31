<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\{BankTransaction, Bank, Store, Invoice};

class BankTransactionController
{
    public function index(): void
    {
        Middleware::owner();

        $bankId = !empty($_GET['bank_id']) ? (int)$_GET['bank_id'] : null;
        $type = $_GET['type'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $transactions = BankTransaction::all(
            $bankId,
            $type ?: null,
            $dateFrom ?: null,
            $dateTo ?: null
        );

        $banks = Bank::all();

        view('layouts/app', [
            'content' => 'bank-transactions/index',
            'data' => [
                'pageTitle'    => 'Bank tranzakciók',
                'activeTab'    => 'bank_transactions',
                'transactions' => $transactions,
                'banks'        => $banks,
                'filters'      => ['bank_id' => $bankId, 'type' => $type, 'date_from' => $dateFrom, 'date_to' => $dateTo],
            ]
        ]);
    }

    /**
     * Kártyás forgalom beérkezés rögzítése
     */
    public function createCard(): void
    {
        Middleware::owner();

        $banks = Bank::all();
        $stores = Store::all();

        view('layouts/app', [
            'content' => 'bank-transactions/card-form',
            'data' => [
                'pageTitle' => 'Kártyás forgalom beérkezés',
                'activeTab' => 'bank_transactions',
                'banks'     => $banks,
                'stores'    => $stores,
            ]
        ]);
    }

    public function storeCard(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bankId = (int)($_POST['bank_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
        $storeIds = $_POST['store_ids'] ?? [];
        $notes = trim($_POST['notes'] ?? '') ?: null;

        if (!$bankId || $amount <= 0 || !$dateFrom || !$dateTo || empty($storeIds)) {
            save_old_input();
            set_flash('error', 'Minden mező kitöltése kötelező (bank, összeg, időszak, boltok).');
            redirect('/bank-transactions/card/create');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            save_old_input();
            set_flash('error', 'Érvénytelen dátum formátum.');
            redirect('/bank-transactions/card/create');
        }

        $data = [
            'bank_id'          => $bankId,
            'type'             => 'kartya_beerkezes',
            'amount'           => $amount,
            'transaction_date' => $transactionDate,
            'date_from'        => $dateFrom,
            'date_to'          => $dateTo,
            'notes'            => $notes,
            'recorded_by'      => Auth::id(),
        ];

        $id = BankTransaction::create($data);
        BankTransaction::assignStores($id, $storeIds);
        AuditLog::log('create', 'bank_transactions', $id, null, $data);
        set_flash('success', 'Kártyás forgalom beérkezés rögzítve.');
        redirect('/bank-transactions');
    }

    /**
     * Szolgáltatói levonás rögzítése
     */
    public function createProvider(): void
    {
        Middleware::owner();

        $banks = Bank::all();
        $invoices = $this->getUnlinkedInvoices();

        view('layouts/app', [
            'content' => 'bank-transactions/provider-form',
            'data' => [
                'pageTitle' => 'Szolgáltatói levonás',
                'activeTab' => 'bank_transactions',
                'banks'     => $banks,
                'invoices'  => $invoices,
            ]
        ]);
    }

    public function storeProvider(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bankId = (int)($_POST['bank_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
        $providerName = trim($_POST['provider_name'] ?? '');
        $invoiceId = !empty($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : null;
        $notes = trim($_POST['notes'] ?? '') ?: null;

        if (!$bankId || $amount <= 0 || empty($providerName)) {
            save_old_input();
            set_flash('error', 'Bank, összeg és szolgáltató neve kötelező.');
            redirect('/bank-transactions/provider/create');
        }

        $data = [
            'bank_id'          => $bankId,
            'type'             => 'szolgaltato_levon',
            'amount'           => $amount,
            'transaction_date' => $transactionDate,
            'provider_name'    => $providerName,
            'invoice_id'       => $invoiceId,
            'notes'            => $notes,
            'recorded_by'      => Auth::id(),
        ];

        $id = BankTransaction::create($data);
        AuditLog::log('create', 'bank_transactions', $id, null, $data);
        set_flash('success', 'Szolgáltatói levonás rögzítve.');
        redirect('/bank-transactions');
    }

    /**
     * Szolgáltatói levonás összekötése számlával
     */
    public function edit(string $id): void
    {
        Middleware::owner();

        $tx = BankTransaction::find((int)$id);
        if (!$tx) redirect('/bank-transactions');

        $banks = Bank::allWithInactive();
        $allBanks = array_filter($banks, fn($b) => $b['is_active'] && !$b['is_loan']);
        $loans = Bank::allLoans();
        $stores = \App\Models\Store::all();

        view('layouts/app', [
            'content' => 'bank-transactions/edit-form',
            'data' => [
                'pageTitle'   => 'Tranzakció szerkesztése',
                'activeTab'   => 'bank_transactions',
                'transaction' => $tx,
                'banks'       => $allBanks,
                'loans'       => $loans,
                'stores'      => $stores,
            ]
        ]);
    }

    public function update(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $tx = BankTransaction::find((int)$id);
        if (!$tx) redirect('/bank-transactions');

        $db = \App\Core\Database::getInstance();
        $commission = isset($_POST['commission']) && $_POST['commission'] !== '' ? (float)$_POST['commission'] : $tx['commission'] ?? null;

        $stmt = $db->prepare(
            'UPDATE bank_transactions SET bank_id = :bank_id, amount = :amount, commission = :commission, source_amount = :source_amount,
             transaction_date = :tdate, date_from = :df, date_to = :dt,
             provider_name = :prov, loan_bank_id = :loan, target_bank_id = :target, notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            'bank_id'  => (int)($_POST['bank_id'] ?? $tx['bank_id']),
            'amount'   => (float)($_POST['amount'] ?? $tx['amount']),
            'commission' => $commission,
            'source_amount' => !empty($_POST['source_amount']) ? (float)$_POST['source_amount'] : $tx['source_amount'],
            'tdate'    => $_POST['transaction_date'] ?? $tx['transaction_date'],
            'df'       => $_POST['date_from'] ?? $tx['date_from'],
            'dt'       => $_POST['date_to'] ?? $tx['date_to'],
            'prov'     => trim($_POST['provider_name'] ?? '') ?: $tx['provider_name'],
            'loan'     => !empty($_POST['loan_bank_id']) ? (int)$_POST['loan_bank_id'] : $tx['loan_bank_id'],
            'target'   => !empty($_POST['target_bank_id']) ? (int)$_POST['target_bank_id'] : $tx['target_bank_id'],
            'notes'    => trim($_POST['notes'] ?? '') ?: null,
            'id'       => (int)$id,
        ]);

        AuditLog::log('update', 'bank_transactions', (int)$id, $tx, $_POST);
        set_flash('success', 'Tranzakció frissítve.');
        redirect('/bank-transactions');
    }

    public function linkInvoice(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $invoiceId = !empty($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : null;
        BankTransaction::linkInvoice((int)$id, $invoiceId);
        AuditLog::log('update', 'bank_transactions', (int)$id, null, ['invoice_id' => $invoiceId]);
        set_flash('success', 'Számla összekötve.');
        redirect('/bank-transactions');
    }

    /**
     * Hitel törlesztő részlet rögzítése
     */
    public function createLoan(): void
    {
        Middleware::owner();

        $banks = Bank::all(); // csak bankszámlák (nem hitelek)
        $loans = Bank::allLoans();

        view('layouts/app', [
            'content' => 'bank-transactions/loan-form',
            'data' => [
                'pageTitle' => 'Hitel törlesztő részlet',
                'activeTab' => 'bank_transactions',
                'banks'     => $banks,
                'loans'     => $loans,
            ]
        ]);
    }

    public function storeLoan(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bankId = (int)($_POST['bank_id'] ?? 0);
        $loanBankId = (int)($_POST['loan_bank_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '') ?: null;

        if (!$bankId || !$loanBankId || $amount <= 0) {
            save_old_input();
            set_flash('error', 'Bank, hitel és összeg megadása kötelező.');
            redirect('/bank-transactions/loan/create');
        }

        $data = [
            'bank_id'          => $bankId,
            'type'             => 'hitel_torlesztes',
            'amount'           => $amount,
            'transaction_date' => $transactionDate,
            'loan_bank_id'     => $loanBankId,
            'notes'            => $notes,
            'recorded_by'      => Auth::id(),
        ];

        $id = BankTransaction::create($data);
        AuditLog::log('create', 'bank_transactions', $id, null, $data);

        $loan = Bank::find($loanBankId);
        set_flash('success', 'Hitel törlesztés rögzítve: ' . format_money($amount) . ' → ' . e($loan['name'] ?? ''));
        redirect('/bank-transactions');
    }

    /**
     * Számlák közötti átutalás
     */
    public function createTransfer(): void
    {
        Middleware::owner();

        $banks = Bank::allWithInactive();
        $banks = array_filter($banks, fn($b) => $b['is_active'] && !$b['is_loan']);

        view('layouts/app', [
            'content' => 'bank-transactions/transfer-form',
            'data' => [
                'pageTitle' => 'Számlák közötti átutalás',
                'activeTab' => 'bank_transactions',
                'banks'     => $banks,
            ]
        ]);
    }

    public function storeTransfer(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bankId = (int)($_POST['bank_id'] ?? 0);
        $targetBankId = (int)($_POST['target_bank_id'] ?? 0);
        $sourceAmount = (float)($_POST['source_amount'] ?? 0);
        $targetAmount = (float)($_POST['amount'] ?? 0);
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '') ?: null;

        if (!$bankId || !$targetBankId || $sourceAmount <= 0 || $targetAmount <= 0) {
            save_old_input();
            set_flash('error', 'Mindkét számla és összeg megadása kötelező.');
            redirect('/bank-transactions/transfer/create');
        }

        if ($bankId === $targetBankId) {
            save_old_input();
            set_flash('error', 'A küldő és fogadó számla nem lehet ugyanaz.');
            redirect('/bank-transactions/transfer/create');
        }

        $targetBank = Bank::find($targetBankId);

        $data = [
            'bank_id'          => $bankId,
            'type'             => 'szamla_kozti',
            'amount'           => $targetAmount,
            'source_amount'    => $sourceAmount,
            'target_currency'  => $targetBank['currency'] ?? 'HUF',
            'target_bank_id'   => $targetBankId,
            'transaction_date' => $transactionDate,
            'notes'            => $notes,
            'recorded_by'      => Auth::id(),
        ];

        $id = BankTransaction::create($data);
        AuditLog::log('create', 'bank_transactions', $id, null, $data);
        set_flash('success', 'Átutalás rögzítve.');
        redirect('/bank-transactions');
    }

    /**
     * Banki jutalék rögzítése
     */
    public function createCommission(): void
    {
        Middleware::owner();

        $banks = Bank::all();

        view('layouts/app', [
            'content' => 'bank-transactions/commission-form',
            'data' => [
                'pageTitle' => 'Banki jutalék',
                'activeTab' => 'bank_transactions',
                'banks'     => $banks,
            ]
        ]);
    }

    public function storeCommission(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bankId = (int)($_POST['bank_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '') ?: null;

        if (!$bankId || $amount <= 0) {
            save_old_input();
            set_flash('error', 'Bank és összeg megadása kötelező.');
            redirect('/bank-transactions/commission/create');
        }

        $data = [
            'bank_id'          => $bankId,
            'type'             => 'banki_jutalek',
            'amount'           => $amount,
            'transaction_date' => $transactionDate,
            'notes'            => $notes,
            'recorded_by'      => Auth::id(),
        ];

        $id = BankTransaction::create($data);
        AuditLog::log('create', 'bank_transactions', $id, null, $data);
        set_flash('success', 'Banki jutalék rögzítve: ' . format_money($amount));
        redirect('/bank-transactions');
    }

    /**
     * Tagi kölcsön rögzítése
     */
    public function createOwnerLoan(): void
    {
        Middleware::owner();

        $banks = Bank::all();

        view('layouts/app', [
            'content' => 'bank-transactions/owner-loan-form',
            'data' => [
                'pageTitle' => 'Tagi kölcsön',
                'activeTab' => 'bank_transactions',
                'banks'     => $banks,
            ]
        ]);
    }

    public function storeOwnerLoan(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bankId = (int)($_POST['bank_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $direction = $_POST['direction'] ?? 'in';
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '') ?: null;

        if (!$bankId || $amount <= 0) {
            save_old_input();
            set_flash('error', 'Bank és összeg megadása kötelező.');
            redirect('/bank-transactions/owner-loan/create');
        }

        $type = $direction === 'out' ? 'tagi_kolcson_ki' : 'tagi_kolcson_be';

        $data = [
            'bank_id'          => $bankId,
            'type'             => $type,
            'amount'           => $amount,
            'transaction_date' => $transactionDate,
            'notes'            => $notes,
            'recorded_by'      => Auth::id(),
        ];

        $id = BankTransaction::create($data);
        AuditLog::log('create', 'bank_transactions', $id, null, $data);
        $label = $direction === 'out' ? 'Tagi kölcsön visszafizetés' : 'Tagi kölcsön befizetés';
        set_flash('success', $label . ' rögzítve: ' . format_money($amount));
        redirect('/bank-transactions');
    }

    /**
     * Adó kifizetés rögzítése
     */
    public function createTax(): void
    {
        Middleware::owner();
        view('layouts/app', [
            'content' => 'bank-transactions/tax-form',
            'data' => [
                'pageTitle' => 'Adó kifizetés',
                'activeTab' => 'bank_transactions',
                'banks'     => Bank::all(),
            ]
        ]);
    }

    public function storeTax(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bankId = (int)($_POST['bank_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '') ?: null;

        if (!$bankId || $amount <= 0) {
            save_old_input();
            set_flash('error', 'Bank és összeg megadása kötelező.');
            redirect('/bank-transactions/tax/create');
        }

        $data = [
            'bank_id'          => $bankId,
            'type'             => 'ado_kifizetes',
            'amount'           => $amount,
            'transaction_date' => $transactionDate,
            'notes'            => $notes,
            'recorded_by'      => Auth::id(),
        ];

        $id = BankTransaction::create($data);
        AuditLog::log('create', 'bank_transactions', $id, null, $data);
        set_flash('success', 'Adó kifizetés rögzítve: ' . format_money($amount));
        redirect('/bank-transactions');
    }

    public function destroy(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $tx = BankTransaction::find((int)$id);
        if ($tx) {
            BankTransaction::delete((int)$id);
            AuditLog::log('delete', 'bank_transactions', (int)$id, $tx, null);
            set_flash('success', 'Tranzakció törölve.');
        }
        redirect('/bank-transactions');
    }

    /**
     * API: Bruttó összeg lekérése kiválasztott boltok+időszak alapján
     */
    public function apiGross(): void
    {
        Middleware::owner();

        $storeIds = $_GET['store_ids'] ?? [];
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        if (empty($storeIds) || !$dateFrom || !$dateTo) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['gross' => 0]);
            return;
        }

        $db = \App\Core\Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($storeIds), '?'));
        $params = array_map('intval', $storeIds);
        $params[] = $dateFrom;
        $params[] = $dateTo;

        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM financial_records
             WHERE store_id IN ({$placeholders})
             AND purpose = 'napi_bankkartya'
             AND record_date >= ? AND record_date <= ?"
        );
        $stmt->execute($params);
        $gross = (float)$stmt->fetchColumn();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['gross' => $gross], JSON_UNESCAPED_UNICODE);
    }

    private function getUnlinkedInvoices(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->query(
            "SELECT i.id, i.invoice_number, sp.name as supplier_name, i.amount, i.invoice_date
             FROM invoices i
             JOIN suppliers sp ON i.supplier_id = sp.id
             WHERE i.store_id IS NULL
             ORDER BY i.invoice_date DESC
             LIMIT 50"
        )->fetchAll();
    }
}
