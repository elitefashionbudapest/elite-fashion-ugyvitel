<?php

namespace App\Controllers;

use App\Core\{Middleware, AuditLog};
use App\Models\CompanySetting;

class CompanySettingsController
{
    public function index(): void
    {
        Middleware::owner();

        $settings = CompanySetting::getAll();

        view('layouts/app', [
            'content' => 'settings/company',
            'data' => [
                'pageTitle' => 'Cégbeállítások',
                'activeTab' => 'settings',
                'settings'  => $settings,
            ]
        ]);
    }

    public function save(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $fields = [
            'company_name', 'company_name_variants', 'company_tax_number', 'company_address',
            'imap_host', 'imap_port', 'imap_email', 'imap_password', 'imap_encryption',
            'anthropic_api_key',
        ];

        foreach ($fields as $field) {
            $value = trim($_POST[$field] ?? '');
            CompanySetting::set($field, $value ?: null);
        }

        AuditLog::log('update', 'company_settings', null, null, ['fields' => $fields]);
        set_flash('success', 'Cégbeállítások mentve.');
        redirect('/settings/company');
    }
}
