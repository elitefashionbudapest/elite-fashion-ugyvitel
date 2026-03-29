<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\{User, Store};

class UserController
{
    public function index(): void
    {
        Middleware::owner();
        $users = User::all();
        view('layouts/app', [
            'content' => 'users/index',
            'data' => ['pageTitle' => 'Fiókok', 'activeTab' => 'users', 'users' => $users]
        ]);
    }

    public function create(): void
    {
        Middleware::owner();
        $stores = Store::all();
        view('layouts/app', [
            'content' => 'users/form',
            'data' => ['pageTitle' => 'Új fiók', 'activeTab' => 'users', 'user' => null, 'stores' => $stores]
        ]);
    }

    public function store(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $data = [
            'name'     => trim($_POST['name'] ?? ''),
            'email'    => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role'     => $_POST['role'] ?? 'bolt',
            'store_id' => !empty($_POST['store_id']) ? (int)$_POST['store_id'] : null,
        ];

        $errors = [];
        if (empty($data['name'])) $errors[] = 'A név megadása kötelező.';
        if (empty($data['email'])) $errors[] = 'Az email megadása kötelező.';
        if (empty($data['password'])) $errors[] = 'A jelszó megadása kötelező.';
        if (User::findByEmail($data['email'])) $errors[] = 'Ez az email cím már foglalt.';
        if ($data['role'] === 'bolt' && empty($data['store_id'])) $errors[] = 'Bolt fiókhoz bolt kiválasztása kötelező.';

        if (!empty($errors)) {
            save_old_input();
            set_flash('error', implode('<br>', $errors));
            redirect('/users/create');
        }

        $id = User::create($data);
        AuditLog::log('create', 'users', $id, null, ['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role']]);
        set_flash('success', 'Fiók sikeresen létrehozva.');
        redirect('/users');
    }

    public function edit(string $id): void
    {
        Middleware::owner();
        $user = User::find((int)$id);
        if (!$user) { redirect('/users'); }

        $stores = Store::all();
        view('layouts/app', [
            'content' => 'users/form',
            'data' => ['pageTitle' => 'Fiók szerkesztése', 'activeTab' => 'users', 'user' => $user, 'stores' => $stores]
        ]);
    }

    public function update(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $user = User::find((int)$id);
        if (!$user) { redirect('/users'); }

        $data = [
            'name'     => trim($_POST['name'] ?? ''),
            'email'    => trim($_POST['email'] ?? ''),
            'role'     => $_POST['role'] ?? $user['role'],
            'store_id' => !empty($_POST['store_id']) ? (int)$_POST['store_id'] : null,
            'is_active'=> isset($_POST['is_active']) ? 1 : 0,
        ];

        // Jelszó csak ha megadva
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }

        // Email duplikáció ellenőrzés
        $existing = User::findByEmail($data['email']);
        if ($existing && $existing['id'] !== (int)$id) {
            save_old_input();
            set_flash('error', 'Ez az email cím már foglalt.');
            redirect("/users/{$id}/edit");
        }

        User::update((int)$id, $data);
        AuditLog::log('update', 'users', (int)$id, $user, $data);
        set_flash('success', 'Fiók sikeresen frissítve.');
        redirect('/users');
    }

    public function destroy(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        // Önmaga nem törölheti magát
        if ((int)$id === Auth::id()) {
            set_flash('error', 'Saját fiókot nem lehet törölni.');
            redirect('/users');
        }

        $user = User::find((int)$id);
        if ($user) {
            User::delete((int)$id);
            AuditLog::log('delete', 'users', (int)$id, $user, null);
            set_flash('success', 'Fiók sikeresen törölve.');
        }
        redirect('/users');
    }
}
