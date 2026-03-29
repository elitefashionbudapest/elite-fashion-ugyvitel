<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\{User, TabPermission};

class SettingsController
{
    public function permissions(): void
    {
        Middleware::owner();

        $storeUsers = User::getByRole('bolt');
        $accountantUsers = User::getByRole('konyvelo');
        // Tulajdonosok is (kivéve saját magam)
        $ownerUsers = array_filter(User::getByRole('tulajdonos'), fn($u) => $u['id'] !== Auth::id());
        $allUsers = array_merge($ownerUsers, $storeUsers, $accountantUsers);
        $allTabs = TabPermission::TABS;

        // Minden fiók jogosultságai
        $permissions = [];
        foreach ($allUsers as $user) {
            $permissions[$user['id']] = TabPermission::getAllForUser($user['id']);
        }

        view('layouts/app', [
            'content' => 'settings/permissions',
            'data' => [
                'pageTitle'      => 'Tab jogosultságok',
                'activeTab'      => 'settings',
                'ownerUsers'     => $ownerUsers,
                'storeUsers'     => $storeUsers,
                'accountantUsers'=> $accountantUsers,
                'allTabs'        => $allTabs,
                'permissions'    => $permissions,
            ]
        ]);
    }

    public function savePermissions(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $perms = $_POST['perms'] ?? [];

        // Összes felhasználó akinek a jogosultságát kezelhetjük
        $storeUsers = User::getByRole('bolt');
        $accountantUsers = User::getByRole('konyvelo');
        $ownerUsers = array_filter(User::getByRole('tulajdonos'), fn($u) => $u['id'] !== Auth::id());
        $allUsers = array_merge($ownerUsers, $storeUsers, $accountantUsers);
        $allTabs = TabPermission::TABS;

        // Minden felhasználó minden tabját mentjük (ha nincs pipálva = false)
        foreach ($allUsers as $user) {
            $uid = $user['id'];
            foreach ($allTabs as $slug => $tab) {
                $canView = isset($perms[$uid][$slug]['view']);
                $canEdit = isset($perms[$uid][$slug]['edit']);
                TabPermission::save((int)$uid, $slug, $canView, $canEdit);
            }
        }

        AuditLog::log('update', 'tab_permissions', null, null, ['updated' => array_map(fn($u) => $u['id'], $allUsers)]);
        set_flash('success', 'Jogosultságok sikeresen mentve.');
        redirect('/settings/permissions');
    }
}
