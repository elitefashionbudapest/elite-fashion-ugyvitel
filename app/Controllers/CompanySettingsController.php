<?php

namespace App\Controllers;

use App\Core\{Middleware, AuditLog};
use App\Models\CompanySetting;
use App\Services\GmailApiClient;

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
            'company_name', 'company_name_variants', 'company_tax_number', 'company_eu_vat', 'company_address',
            'google_client_id', 'google_client_secret',
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

    public function connectGmail(): void
    {
        Middleware::owner();

        // A client ID és secret a cégbeállításokból jön (google_client_id, google_client_secret)
        // Ezeket az .env fájlból vagy a DB-ből olvassa a GmailApiClient

        $gmail = new GmailApiClient();
        header('Location: ' . $gmail->getAuthUrl());
        exit;
    }

    public function googleCallback(): void
    {
        Middleware::owner();

        $code = $_GET['code'] ?? '';
        if (!$code) {
            set_flash('error', 'Google hitelesítés sikertelen — nincs kód.');
            redirect('/settings/company');
        }

        $gmail = new GmailApiClient();
        if ($gmail->handleCallback($code)) {
            set_flash('success', 'Gmail sikeresen csatlakoztatva: ' . $gmail->getEmail());
        } else {
            set_flash('error', 'Gmail csatlakoztatás sikertelen.');
        }
        redirect('/settings/company');
    }

    public function disconnectGmail(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        CompanySetting::set('google_access_token', null);
        CompanySetting::set('google_refresh_token', null);
        CompanySetting::set('google_token_expires', null);
        CompanySetting::set('google_email', null);

        set_flash('success', 'Gmail lecsatlakoztatva.');
        redirect('/settings/company');
    }

    public function testImap(): void
    {
        Middleware::owner();

        $host = CompanySetting::get('imap_host', '');
        $port = CompanySetting::get('imap_port', '993');
        $email = CompanySetting::get('imap_email', '');
        $password = CompanySetting::get('imap_password', '');
        $encryption = CompanySetting::get('imap_encryption', 'ssl');

        header('Content-Type: application/json; charset=utf-8');

        if (!$host || !$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Hiányzó IMAP beállítások. Először mentsd el az adatokat!']);
            return;
        }

        if (!function_exists('imap_open')) {
            echo json_encode(['success' => false, 'message' => 'PHP IMAP kiterjesztés nem elérhető a szerveren.']);
            return;
        }

        $enc = $encryption === 'none' ? '/novalidate-cert' : '/' . $encryption;
        $mailbox = '{' . $host . ':' . $port . '/imap' . $enc . '}INBOX';

        $imap = @imap_open($mailbox, $email, $password);

        if (!$imap) {
            $error = imap_last_error() ?: 'Ismeretlen hiba';
            echo json_encode(['success' => false, 'message' => 'Kapcsolódás sikertelen: ' . $error]);
            return;
        }

        $info = imap_mailboxmsginfo($imap);
        $msgCount = $info->Nmsgs ?? 0;
        imap_close($imap);

        echo json_encode(['success' => true, 'message' => "Kapcsolat OK! Postafiók: {$email} — {$msgCount} levél"]);
    }

    public function testApi(): void
    {
        Middleware::owner();

        $apiKey = CompanySetting::get('anthropic_api_key', '');

        header('Content-Type: application/json; charset=utf-8');

        if (!$apiKey) {
            echo json_encode(['success' => false, 'message' => 'Hiányzó API kulcs. Először mentsd el!']);
            return;
        }

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 20,
                'messages' => [['role' => 'user', 'content' => 'Válaszolj: OK']],
            ]),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response) {
            echo json_encode(['success' => false, 'message' => 'Nem sikerült elérni az Anthropic API-t.']);
            return;
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 && isset($data['content'])) {
            $model = $data['model'] ?? 'ismeretlen';
            echo json_encode(['success' => true, 'message' => "API kulcs érvényes! Modell: {$model}"]);
        } else {
            $error = $data['error']['message'] ?? 'Érvénytelen API kulcs';
            echo json_encode(['success' => false, 'message' => 'API hiba: ' . $error]);
        }
    }
}
