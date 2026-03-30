<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\Store;

class StoreController
{
    public function index(): void
    {
        Middleware::owner();
        $stores = Store::all();
        view('layouts/app', [
            'content' => 'stores/index',
            'data' => ['pageTitle' => 'Boltok', 'activeTab' => 'stores', 'stores' => $stores]
        ]);
    }

    public function create(): void
    {
        Middleware::owner();
        view('layouts/app', [
            'content' => 'stores/form',
            'data' => ['pageTitle' => 'Új bolt', 'activeTab' => 'stores', 'store' => null]
        ]);
    }

    public function store(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            save_old_input();
            set_flash('error', 'A bolt neve kötelező.');
            redirect('/stores/create');
        }

        $openDays = implode(',', $_POST['open_days'] ?? ['1','2','3','4','5','6']);
        $id = Store::create(['name' => $name, 'open_days' => $openDays]);
        AuditLog::log('create', 'stores', $id, null, ['name' => $name]);
        set_flash('success', 'Bolt sikeresen létrehozva.');
        redirect('/stores');
    }

    public function edit(string $id): void
    {
        Middleware::owner();
        $store = Store::find((int)$id);
        if (!$store) { redirect('/stores'); }

        view('layouts/app', [
            'content' => 'stores/form',
            'data' => ['pageTitle' => 'Bolt szerkesztése', 'activeTab' => 'stores', 'store' => $store]
        ]);
    }

    public function update(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $store = Store::find((int)$id);
        if (!$store) { redirect('/stores'); }

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            save_old_input();
            set_flash('error', 'A bolt neve kötelező.');
            redirect("/stores/{$id}/edit");
        }

        $openDays = implode(',', $_POST['open_days'] ?? ['1','2','3','4','5','6']);
        Store::update((int)$id, ['name' => $name, 'open_days' => $openDays]);
        AuditLog::log('update', 'stores', (int)$id, ['name' => $store['name']], ['name' => $name]);
        set_flash('success', 'Bolt sikeresen frissítve.');
        redirect('/stores');
    }

    public function destroy(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $store = Store::find((int)$id);
        if ($store) {
            Store::delete((int)$id);
            AuditLog::log('delete', 'stores', (int)$id, $store, null);
            set_flash('success', 'Bolt sikeresen törölve.');
        }
        redirect('/stores');
    }
}
