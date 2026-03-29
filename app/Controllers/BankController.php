<?php

namespace App\Controllers;

use App\Core\{Middleware, AuditLog};
use App\Models\Bank;

class BankController
{
    public function index(): void
    {
        Middleware::owner();
        $banks = Bank::allWithBalance();
        view('layouts/app', [
            'content' => 'banks/index',
            'data' => ['pageTitle' => 'Bankszámlák', 'activeTab' => 'banks', 'banks' => $banks]
        ]);
    }

    public function create(): void
    {
        Middleware::owner();
        view('layouts/app', [
            'content' => 'banks/form',
            'data' => ['pageTitle' => 'Új bankszámla', 'activeTab' => 'banks', 'bank' => null]
        ]);
    }

    public function store(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            save_old_input();
            set_flash('error', 'A bank neve kötelező.');
            redirect('/banks/create');
        }

        $id = Bank::create([
            'name'            => $name,
            'currency'        => $_POST['currency'] ?? 'HUF',
            'account_number'  => trim($_POST['account_number'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'opening_balance' => (float)($_POST['opening_balance'] ?? 0),
            'min_balance'     => ($_POST['min_balance'] ?? '') !== '' ? (float)$_POST['min_balance'] : null,
            'is_loan'         => (int)($_POST['is_loan'] ?? 0),
        ]);
        AuditLog::log('create', 'banks', $id, null, ['name' => $name]);
        set_flash('success', 'Bankszámla sikeresen létrehozva.');
        redirect('/banks');
    }

    public function edit(string $id): void
    {
        Middleware::owner();
        $bank = Bank::find((int)$id);
        if (!$bank) redirect('/banks');

        view('layouts/app', [
            'content' => 'banks/form',
            'data' => ['pageTitle' => 'Bankszámla szerkesztése', 'activeTab' => 'banks', 'bank' => $bank]
        ]);
    }

    public function update(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bank = Bank::find((int)$id);
        if (!$bank) redirect('/banks');

        Bank::update((int)$id, [
            'name'            => trim($_POST['name'] ?? ''),
            'account_number'  => trim($_POST['account_number'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'currency'        => $_POST['currency'] ?? 'HUF',
            'opening_balance' => (float)($_POST['opening_balance'] ?? 0),
            'min_balance'     => ($_POST['min_balance'] ?? '') !== '' ? (float)$_POST['min_balance'] : null,
            'is_loan'         => (int)($_POST['is_loan'] ?? 0),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ]);
        AuditLog::log('update', 'banks', (int)$id, $bank, $_POST);
        set_flash('success', 'Bankszámla frissítve.');
        redirect('/banks');
    }

    public function destroy(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $bank = Bank::find((int)$id);
        if ($bank) {
            Bank::delete((int)$id);
            AuditLog::log('delete', 'banks', (int)$id, $bank, null);
            set_flash('success', 'Bankszámla törölve.');
        }
        redirect('/banks');
    }
}
