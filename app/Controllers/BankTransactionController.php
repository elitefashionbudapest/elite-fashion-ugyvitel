<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\{BankTransaction, Bank, Store, Invoice};
use App\Services\CsvImportService;

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

    /**
     * Banki kivonat importálás - feltöltő form vagy előnézet
     */
    public function importForm(): void
    {
        Middleware::owner();

        // Ha van session-ben feldolgozott CSV, előnézetet mutatunk
        if (!empty($_SESSION['csv_import']) && isset($_GET['preview'])) {
            $import = $_SESSION['csv_import'];
            $bank = Bank::find($import['bank_id']);

            view('layouts/app', [
                'content' => 'bank-transactions/import-preview',
                'data' => [
                    'pageTitle' => 'Kivonat előnézet',
                    'activeTab' => 'bank_transactions',
                    'rows'      => $import['rows'],
                    'bank_id'   => $import['bank_id'],
                    'bank_name' => $bank['name'] ?? '',
                    'stores'    => Store::all(),
                ]
            ]);
            return;
        }

        view('layouts/app', [
            'content' => 'bank-transactions/import-form',
            'data' => [
                'pageTitle' => 'Kivonat importálás',
                'activeTab' => 'bank_transactions',
                'banks'     => Bank::all(),
            ]
        ]);
    }

    /**
     * CSV feltöltés és feldolgozás
     */
    public function importUpload(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bankId = (int)($_POST['bank_id'] ?? 0);
        if (!$bankId) {
            set_flash('error', 'Válasszon bankszámlát.');
            redirect('/bank-transactions/import');
        }

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            set_flash('error', 'Kérem töltsön fel egy fájlt.');
            redirect('/bank-transactions/import');
        }

        $file = $_FILES['csv_file'];
        if ($file['size'] > 5 * 1024 * 1024) {
            set_flash('error', 'A fájl túl nagy (max 5MB).');
            redirect('/bank-transactions/import');
        }

        $rows = CsvImportService::parseFile($file['tmp_name'], $file['name']);

        if (empty($rows)) {
            set_flash('error', 'A fájl üres vagy nem sikerült feldolgozni. Támogatott: OTP CSV, CIB Excel (.xls/.xlsx).');
            redirect('/bank-transactions/import');
        }

        // Típus tipp és duplikátum ellenőrzés
        $db = \App\Core\Database::getInstance();
        foreach ($rows as &$row) {
            $row['suggested_type'] = CsvImportService::suggestType($row);

            // Duplikátum keresés — összeg + dátum
            $stmt = $db->prepare(
                'SELECT id, type, amount, transaction_date, notes FROM bank_transactions
                 WHERE bank_id = :bank_id AND transaction_date = :d AND amount = :a
                 LIMIT 1'
            );
            $stmt->execute([
                'bank_id' => $bankId,
                'd'       => $row['booking_date'],
                'a'       => $row['amount'],
            ]);
            $match = $stmt->fetch();

            if ($match) {
                $row['duplicate'] = true;
                $row['duplicate_match'] = $match;
            } else {
                // Laza keresés: csak összeg (±1 nap eltéréssel — bank néha más napra könyveli)
                $stmt = $db->prepare(
                    'SELECT id, type, amount, transaction_date, notes FROM bank_transactions
                     WHERE bank_id = :bank_id
                       AND ABS(DATEDIFF(transaction_date, :d)) <= 1
                       AND amount = :a
                     LIMIT 1'
                );
                $stmt->execute([
                    'bank_id' => $bankId,
                    'd'       => $row['booking_date'],
                    'a'       => $row['amount'],
                ]);
                $looseMatch = $stmt->fetch();

                if ($looseMatch) {
                    $row['duplicate'] = true;
                    $row['duplicate_match'] = $looseMatch;
                    $row['duplicate_loose'] = true;
                } else {
                    $row['duplicate'] = false;
                }
            }
        }

        $_SESSION['csv_import'] = [
            'bank_id' => $bankId,
            'rows'    => $rows,
        ];

        redirect('/bank-transactions/import?preview=1');
    }

    /**
     * Kijelölt sorok importálása
     */
    public function importStore(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        if (empty($_SESSION['csv_import'])) {
            set_flash('error', 'Nincs feldolgozott kivonat.');
            redirect('/bank-transactions/import');
        }

        $import = $_SESSION['csv_import'];
        $bankId = $import['bank_id'];
        $rows = $import['rows'];
        $selected = $_POST['selected'] ?? [];
        $types = $_POST['types'] ?? [];
        $storeIds = $_POST['store_ids'] ?? [];
        $dateFroms = $_POST['date_from'] ?? [];
        $dateTos = $_POST['date_to'] ?? [];

        if (empty($selected)) {
            set_flash('error', 'Jelöljön ki legalább egy sort.');
            redirect('/bank-transactions/import?preview=1');
        }

        $count = 0;
        foreach ($selected as $index) {
            $index = (int)$index;
            if (!isset($rows[$index])) continue;

            $row = $rows[$index];
            $type = $types[$index] ?? '';

            if (empty($type) || !isset(BankTransaction::TYPES[$type])) continue;
            // Befizetés boltból csak ellenőrzésre - már a financial_records-ban van
            if ($type === 'befizetes_boltbol') continue;

            // Notes összeállítása
            $notes = [];
            if ($row['partner_name']) $notes[] = $row['partner_name'];
            if ($row['description']) $notes[] = $row['description'];
            if ($row['reference']) $notes[] = $row['reference'];
            $notesStr = implode(' — ', $notes) ?: null;

            // Kártyás beérkezésnél a felhasználó által megadott időszakot használjuk
            $dateFrom = ($type === 'kartya_beerkezes' && !empty($dateFroms[$index]))
                ? $dateFroms[$index]
                : (($type === 'kartya_beerkezes') ? $row['booking_date'] : null);
            $dateTo = ($type === 'kartya_beerkezes' && !empty($dateTos[$index]))
                ? $dateTos[$index]
                : (($type === 'kartya_beerkezes') ? $row['booking_date'] : null);

            $data = [
                'bank_id'          => $bankId,
                'type'             => $type,
                'amount'           => $row['amount'],
                'transaction_date' => $row['booking_date'],
                'date_from'        => $dateFrom,
                'date_to'          => $dateTo,
                'provider_name'    => ($type === 'szolgaltato_levon') ? ($row['partner_name'] ?: null) : null,
                'notes'            => $notesStr,
                'recorded_by'      => Auth::id(),
            ];

            $id = BankTransaction::create($data);

            // Kártyás beérkezésnél boltok hozzárendelése
            if ($type === 'kartya_beerkezes' && !empty($storeIds[$index])) {
                BankTransaction::assignStores($id, $storeIds[$index]);
            }

            // Szolgáltatói levonásnál: automatikus számla-összekötés (dupla levonás megelőzése)
            if ($type === 'szolgaltato_levon') {
                $db = \App\Core\Database::getInstance();
                $invStmt = $db->prepare(
                    "SELECT i.id FROM invoices i
                     WHERE i.paid_from_bank_id = :bank_id AND i.is_paid = 1
                     AND ABS(i.amount - :amount) < 1
                     AND ABS(DATEDIFF(i.invoice_date, :date)) <= 3
                     AND NOT EXISTS (SELECT 1 FROM bank_transactions bt WHERE bt.invoice_id = i.id)
                     LIMIT 1"
                );
                $invStmt->execute([
                    'bank_id' => $bankId,
                    'amount'  => $row['amount'],
                    'date'    => $row['booking_date'],
                ]);
                $matchedInvoice = $invStmt->fetchColumn();
                if ($matchedInvoice) {
                    BankTransaction::linkInvoice($id, (int)$matchedInvoice);
                }
            }

            AuditLog::log('create', 'bank_transactions', $id, null, array_merge($data, ['source' => 'csv_import']));
            $count++;
        }

        unset($_SESSION['csv_import']);
        set_flash('success', $count . ' tranzakció sikeresen importálva.');
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
        redirect_back('/bank-transactions');
    }

    /**
     * Tömeges törlés (JSON API)
     */
    public function bulkDestroy(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];

        header('Content-Type: application/json; charset=utf-8');

        if (empty($ids)) {
            echo json_encode(['success' => false, 'error' => 'Nincs kijelölt tétel.']);
            exit;
        }

        $deleted = 0;
        foreach ($ids as $id) {
            $id = (int)$id;
            $tx = BankTransaction::find($id);
            if ($tx) {
                BankTransaction::delete($id);
                AuditLog::log('delete', 'bank_transactions', $id, $tx, null);
                $deleted++;
            }
        }

        echo json_encode(['success' => true, 'deleted' => $deleted]);
        exit;
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

        $purpose = $_GET['purpose'] ?? 'napi_bankkartya';
        $allowedPurposes = ['napi_bankkartya', 'bank_kifizetes'];
        if (!in_array($purpose, $allowedPurposes)) $purpose = 'napi_bankkartya';

        $db = \App\Core\Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($storeIds), '?'));
        $params = array_map('intval', $storeIds);
        $params[] = $dateFrom;
        $params[] = $dateTo;

        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM financial_records
             WHERE store_id IN ({$placeholders})
             AND purpose = ?
             AND record_date >= ? AND record_date <= ?"
        );
        $params_with_purpose = array_map('intval', $storeIds);
        $params_with_purpose[] = $purpose;
        $params_with_purpose[] = $dateFrom;
        $params_with_purpose[] = $dateTo;
        $stmt->execute($params_with_purpose);
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

    /**
     * CSV export (szűrőknek megfelelő tételek)
     */
    public function export(): void
    {
        Middleware::owner();

        $bankId = !empty($_GET['bank_id']) ? (int)$_GET['bank_id'] : null;
        $type = $_GET['type'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $transactions = BankTransaction::all($bankId, $type ?: null, $dateFrom ?: null, $dateTo ?: null);

        $filename = 'bank_tranzakciok_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['Dátum', 'Típus', 'Bank', 'Összeg', 'Irány', 'Részletek', 'Bruttó', 'Jutalék'], ';');

        $typeLabels = BankTransaction::TYPES;
        foreach ($transactions as $tx) {
            $isIncoming = in_array($tx['type'], ['kartya_beerkezes', 'tagi_kolcson_be']);
            $details = $tx['provider_name'] ?? $tx['notes'] ?? $tx['loan_name'] ?? '';

            fputcsv($output, [
                $tx['transaction_date'],
                $typeLabels[$tx['type']] ?? $tx['type'],
                $tx['bank_name'],
                $tx['amount'],
                $isIncoming ? 'Bejövő' : 'Kimenő',
                $details,
                $tx['gross_amount'] ?? '',
                $tx['commission'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Egyenleg összevetés — banki kivonat vs rendszer
     */
    public function reconcile(): void
    {
        Middleware::owner();

        $banks = Bank::all();

        view('layouts/app', [
            'content' => 'bank-transactions/reconcile',
            'data' => [
                'pageTitle' => 'Egyenleg összevetés',
                'activeTab' => 'bank_transactions',
                'banks'     => $banks,
            ]
        ]);
    }

    /**
     * Egyenleg összevetés — CSV feltöltés és összehasonlítás
     */
    public function reconcileCompare(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bankId = (int)($_POST['bank_id'] ?? 0);
        $openingBalance = (float)str_replace([' ', ','], ['', '.'], $_POST['opening_balance'] ?? '0');

        if (!$bankId) {
            set_flash('error', 'Válassz bankszámlát.');
            redirect('/bank-transactions/reconcile');
        }

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            set_flash('error', 'Kérem tölts fel egy banki kivonatot.');
            redirect('/bank-transactions/reconcile');
        }

        $file = $_FILES['csv_file'];
        $csvRows = CsvImportService::parseFile($file['tmp_name'], $file['name']);

        if (empty($csvRows)) {
            set_flash('error', 'A fájl üres vagy nem sikerült feldolgozni.');
            redirect('/bank-transactions/reconcile');
        }

        // Dátum tartomány a CSV-ből
        $dates = array_filter(array_column($csvRows, 'booking_date'));
        $dateFrom = min($dates);
        $dateTo = max($dates);

        // Rendszer tranzakciók az adott időszakra
        $systemTx = BankTransaction::all($bankId, null, $dateFrom, $dateTo);

        // CSV tételek összegzése
        $csvTotal = ['in' => 0, 'out' => 0];
        foreach ($csvRows as &$csvRow) {
            $csvRow['matched'] = false;
            $csvRow['match_note'] = null;
            if ($csvRow['direction'] === 'J') {
                $csvTotal['in'] += $csvRow['amount'];
            } else {
                $csvTotal['out'] += $csvRow['amount'];
            }
        }

        // 1. lépés: pontos párosítás (összeg + dátum ±1 nap)
        foreach ($csvRows as &$csvRow) {
            foreach ($systemTx as &$sTx) {
                if (!empty($sTx['_matched'])) continue;
                if (abs((float)$sTx['amount'] - $csvRow['amount']) < 0.01
                    && abs(strtotime($sTx['transaction_date']) - strtotime($csvRow['booking_date'])) <= 86400) {
                    $csvRow['matched'] = true;
                    $csvRow['match_note'] = 'Pontos egyezés';
                    $sTx['_matched'] = true;
                    break;
                }
            }
        }

        // 2. lépés: összeg-csoportos párosítás
        // Több banki tétel összege = egy rendszer tétel (pl. 2 ATM felvétel = 1 tulajdonosi fizetés)
        // Vagy több banki beérkezés = egy összesített kártyás beérkezés
        $unmatchedCsvIdxs = [];
        foreach ($csvRows as $i => &$cr) {
            if (!$cr['matched']) $unmatchedCsvIdxs[] = $i;
        }

        foreach ($systemTx as &$sTx) {
            if (!empty($sTx['_matched'])) continue;
            $sAmount = (float)$sTx['amount'];
            $sDate = $sTx['transaction_date'];

            // Keresünk CSV tételek kombinációját amik összege egyezik
            // Max 5 tételt kombinálunk (teljesítmény miatt)
            $candidates = [];
            foreach ($unmatchedCsvIdxs as $idx) {
                $cr = $csvRows[$idx];
                if (abs(strtotime($cr['booking_date']) - strtotime($sDate)) <= 2 * 86400) {
                    $candidates[] = $idx;
                }
            }

            // 2 tételes kombinációk keresése
            $found = false;
            for ($a = 0; $a < count($candidates) && !$found; $a++) {
                for ($b = $a + 1; $b < count($candidates) && !$found; $b++) {
                    $sum = $csvRows[$candidates[$a]]['amount'] + $csvRows[$candidates[$b]]['amount'];
                    if (abs($sum - $sAmount) < 0.01) {
                        $csvRows[$candidates[$a]]['matched'] = true;
                        $csvRows[$candidates[$a]]['match_note'] = 'Csoportos egyezés (' . number_format($sAmount, 0, ',', ' ') . ' Ft)';
                        $csvRows[$candidates[$b]]['matched'] = true;
                        $csvRows[$candidates[$b]]['match_note'] = 'Csoportos egyezés (' . number_format($sAmount, 0, ',', ' ') . ' Ft)';
                        $sTx['_matched'] = true;
                        $unmatchedCsvIdxs = array_values(array_diff($unmatchedCsvIdxs, [$candidates[$a], $candidates[$b]]));
                        $found = true;
                    }
                }
            }

            // 3 tételes kombinációk keresése
            if (!$found) {
                for ($a = 0; $a < count($candidates) && !$found; $a++) {
                    for ($b = $a + 1; $b < count($candidates) && !$found; $b++) {
                        for ($c = $b + 1; $c < count($candidates) && !$found; $c++) {
                            $sum = $csvRows[$candidates[$a]]['amount'] + $csvRows[$candidates[$b]]['amount'] + $csvRows[$candidates[$c]]['amount'];
                            if (abs($sum - $sAmount) < 0.01) {
                                $matched3 = [$candidates[$a], $candidates[$b], $candidates[$c]];
                                foreach ($matched3 as $m) {
                                    $csvRows[$m]['matched'] = true;
                                    $csvRows[$m]['match_note'] = 'Csoportos egyezés (' . number_format($sAmount, 0, ',', ' ') . ' Ft)';
                                }
                                $sTx['_matched'] = true;
                                $unmatchedCsvIdxs = array_values(array_diff($unmatchedCsvIdxs, $matched3));
                                $found = true;
                            }
                        }
                    }
                }
            }
        }

        // Fordítva is: több rendszer tétel = egy banki tétel
        foreach ($csvRows as &$csvRow) {
            if ($csvRow['matched']) continue;
            $cAmount = $csvRow['amount'];
            $cDate = $csvRow['booking_date'];

            $sysCandidates = [];
            foreach ($systemTx as $si => &$stx) {
                if (!empty($stx['_matched'])) continue;
                if (abs(strtotime($stx['transaction_date']) - strtotime($cDate)) <= 2 * 86400) {
                    $sysCandidates[] = $si;
                }
            }

            for ($a = 0; $a < count($sysCandidates); $a++) {
                for ($b = $a + 1; $b < count($sysCandidates); $b++) {
                    $sum = (float)$systemTx[$sysCandidates[$a]]['amount'] + (float)$systemTx[$sysCandidates[$b]]['amount'];
                    if (abs($sum - $cAmount) < 0.01) {
                        $csvRow['matched'] = true;
                        $csvRow['match_note'] = 'Csoportos egyezés (rendszerben 2 tétel)';
                        $systemTx[$sysCandidates[$a]]['_matched'] = true;
                        $systemTx[$sysCandidates[$b]]['_matched'] = true;
                        break 2;
                    }
                }
            }
        }

        // Rendszer tételek összegzése
        $systemTotal = ['in' => 0, 'out' => 0];
        $unmatchedSystem = [];
        foreach ($systemTx as $sTx) {
            $isIncoming = in_array($sTx['type'], ['kartya_beerkezes', 'tagi_kolcson_be']);
            if ($isIncoming) {
                $systemTotal['in'] += (float)$sTx['amount'];
            } else {
                $systemTotal['out'] += (float)$sTx['amount'];
            }
            if (empty($sTx['_matched'])) {
                $unmatchedSystem[] = $sTx;
            }
        }

        $unmatchedCsv = array_values(array_filter($csvRows, fn($r) => !$r['matched']));

        // Számított egyenleg
        $csvBalance = $openingBalance + $csvTotal['in'] - $csvTotal['out'];
        $systemBalance = $openingBalance + $systemTotal['in'] - $systemTotal['out'];

        $bank = Bank::find($bankId);

        view('layouts/app', [
            'content' => 'bank-transactions/reconcile-result',
            'data' => [
                'pageTitle'       => 'Egyenleg összevetés eredménye',
                'activeTab'       => 'bank_transactions',
                'bank'            => $bank,
                'dateFrom'        => $dateFrom,
                'dateTo'          => $dateTo,
                'openingBalance'  => $openingBalance,
                'csvBalance'      => $csvBalance,
                'systemBalance'   => $systemBalance,
                'csvTotal'        => $csvTotal,
                'systemTotal'     => $systemTotal,
                'unmatchedCsv'    => array_values($unmatchedCsv),
                'unmatchedSystem' => $unmatchedSystem,
                'csvRowCount'     => count($csvRows),
                'matchedCount'    => count(array_filter($csvRows, fn($r) => $r['matched'])),
            ]
        ]);
    }
}
