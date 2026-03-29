<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\{Employee, Store};

class EmployeeController
{
    public function index(): void
    {
        Middleware::owner();
        $employees = Employee::all();
        view('layouts/app', [
            'content' => 'employees/index',
            'data' => ['pageTitle' => 'Dolgozók', 'activeTab' => 'employees', 'employees' => $employees]
        ]);
    }

    public function create(): void
    {
        Middleware::owner();
        $stores = Store::all();
        view('layouts/app', [
            'content' => 'employees/form',
            'data' => ['pageTitle' => 'Új dolgozó', 'activeTab' => 'employees', 'employee' => null, 'stores' => $stores, 'assignedStoreIds' => []]
        ]);
    }

    public function store(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            save_old_input();
            set_flash('error', 'A dolgozó neve kötelező.');
            redirect('/employees/create');
        }

        $id = Employee::create(['name' => $name]);
        $storeIds = $_POST['store_ids'] ?? [];
        if (!empty($storeIds)) {
            Employee::assignStores($id, $storeIds);
        }

        AuditLog::log('create', 'employees', $id, null, ['name' => $name, 'stores' => $storeIds]);
        set_flash('success', 'Dolgozó sikeresen létrehozva.');
        redirect('/employees');
    }

    public function edit(string $id): void
    {
        Middleware::owner();
        $employee = Employee::find((int)$id);
        if (!$employee) { redirect('/employees'); }

        $stores = Store::all();
        $assignedStoreIds = Employee::getStoreIds((int)$id);

        view('layouts/app', [
            'content' => 'employees/form',
            'data' => [
                'pageTitle' => 'Dolgozó szerkesztése',
                'activeTab' => 'employees',
                'employee' => $employee,
                'stores' => $stores,
                'assignedStoreIds' => $assignedStoreIds,
            ]
        ]);
    }

    public function update(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $employee = Employee::find((int)$id);
        if (!$employee) { redirect('/employees'); }

        $name = trim($_POST['name'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            save_old_input();
            set_flash('error', 'A dolgozó neve kötelező.');
            redirect("/employees/{$id}/edit");
        }

        $vacDays = (int)($_POST['vacation_days_total'] ?? 20);
        Employee::update((int)$id, ['name' => $name, 'is_active' => $isActive, 'vacation_days_total' => $vacDays]);
        $storeIds = $_POST['store_ids'] ?? [];
        Employee::assignStores((int)$id, $storeIds);

        AuditLog::log('update', 'employees', (int)$id, $employee, ['name' => $name, 'is_active' => $isActive, 'stores' => $storeIds]);
        set_flash('success', 'Dolgozó sikeresen frissítve.');
        redirect('/employees');
    }

    public function destroy(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $employee = Employee::find((int)$id);
        if ($employee) {
            Employee::delete((int)$id);
            AuditLog::log('delete', 'employees', (int)$id, $employee, null);
            set_flash('success', 'Dolgozó sikeresen törölve.');
        }
        redirect('/employees');
    }
}
