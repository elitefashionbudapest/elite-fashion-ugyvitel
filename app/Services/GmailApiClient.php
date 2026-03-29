<?php

namespace App\Services;

use App\Models\CompanySetting;

class GmailApiClient
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private ?string $accessToken;
    private ?string $refreshToken;

    public function __construct()
    {
        $this->clientId = CompanySetting::get('google_client_id', '');
        $this->clientSecret = CompanySetting::get('google_client_secret', '');
        $this->redirectUri = rtrim(getenv('APP_URL') ?: 'https://elitedivat.hu/ugyvitel', '/') . '/settings/company/google-callback';
        $this->accessToken = CompanySetting::get('google_access_token', '');
        $this->refreshToken = CompanySetting::get('google_refresh_token', '');
    }

    public function getAuthUrl(): string
    {
        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/gmail.readonly',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public function handleCallback(string $code): bool
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code'          => $code,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirectUri,
                'grant_type'    => 'authorization_code',
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (empty($data['access_token'])) return false;

        CompanySetting::set('google_access_token', $data['access_token']);
        if (!empty($data['refresh_token'])) {
            CompanySetting::set('google_refresh_token', $data['refresh_token']);
        }
        CompanySetting::set('google_token_expires', (string)(time() + ($data['expires_in'] ?? 3600)));

        $this->accessToken = $data['access_token'];
        $this->refreshToken = $data['refresh_token'] ?? $this->refreshToken;

        // Email cím lekérése
        $profile = $this->apiRequest('https://www.googleapis.com/gmail/v1/users/me/profile');
        if ($profile && isset($profile['emailAddress'])) {
            CompanySetting::set('google_email', $profile['emailAddress']);
        }

        return true;
    }

    public function isConnected(): bool
    {
        return !empty($this->refreshToken);
    }

    public function getEmail(): string
    {
        return CompanySetting::get('google_email', '');
    }

    private function ensureValidToken(): bool
    {
        $expires = (int)CompanySetting::get('google_token_expires', '0');
        if (time() < $expires - 60) return true;

        if (!$this->refreshToken) return false;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type'    => 'refresh_token',
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (empty($data['access_token'])) return false;

        $this->accessToken = $data['access_token'];
        CompanySetting::set('google_access_token', $data['access_token']);
        CompanySetting::set('google_token_expires', (string)(time() + ($data['expires_in'] ?? 3600)));

        return true;
    }

    public function apiRequest(string $url, string $method = 'GET'): ?array
    {
        if (!$this->ensureValidToken()) return null;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->accessToken],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? json_decode($response, true) : null;
    }

    /**
     * Emailek lekérése tegnap óta
     */
    public function getRecentEmails(): array
    {
        $since = date('Y/m/d', strtotime('-1 day'));
        $query = urlencode("after:{$since}");
        $url = "https://www.googleapis.com/gmail/v1/users/me/messages?q={$query}&maxResults=50";

        $result = $this->apiRequest($url);
        return $result['messages'] ?? [];
    }

    /**
     * Email részleteinek lekérése
     */
    public function getMessage(string $messageId): ?array
    {
        return $this->apiRequest("https://www.googleapis.com/gmail/v1/users/me/messages/{$messageId}?format=full");
    }

    /**
     * Csatolmány letöltése
     */
    public function getAttachment(string $messageId, string $attachmentId): ?string
    {
        $result = $this->apiRequest("https://www.googleapis.com/gmail/v1/users/me/messages/{$messageId}/attachments/{$attachmentId}");
        if (!$result || empty($result['data'])) return null;

        // Gmail API base64url kódolást használ
        $data = str_replace(['-', '_'], ['+', '/'], $result['data']);
        return base64_decode($data);
    }

    /**
     * Email fejléc kinyerése
     */
    public static function getHeader(array $headers, string $name): string
    {
        foreach ($headers as $h) {
            if (strtolower($h['name']) === strtolower($name)) {
                return $h['value'];
            }
        }
        return '';
    }

    /**
     * Email szöveg kinyerése (body)
     */
    public static function getBody(array $payload): string
    {
        if (!empty($payload['body']['data'])) {
            $data = str_replace(['-', '_'], ['+', '/'], $payload['body']['data']);
            return base64_decode($data);
        }

        if (!empty($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                if ($part['mimeType'] === 'text/plain' && !empty($part['body']['data'])) {
                    $data = str_replace(['-', '_'], ['+', '/'], $part['body']['data']);
                    return base64_decode($data);
                }
                if ($part['mimeType'] === 'text/html' && !empty($part['body']['data'])) {
                    $data = str_replace(['-', '_'], ['+', '/'], $part['body']['data']);
                    return strip_tags(base64_decode($data));
                }
                // Rekurzív keresés multipart-ban
                $sub = self::getBody($part);
                if ($sub) return $sub;
            }
        }
        return '';
    }

    /**
     * Csatolmányok listája
     */
    public static function getAttachmentParts(array $payload): array
    {
        $attachments = [];
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!empty($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                $mime = $part['mimeType'] ?? '';
                $filename = $part['filename'] ?? '';
                $attachId = $part['body']['attachmentId'] ?? '';

                if ($attachId && $filename && in_array($mime, $allowedMimes)) {
                    $attachments[] = [
                        'attachmentId' => $attachId,
                        'filename'     => $filename,
                        'mime'         => $mime,
                    ];
                }

                // Rekurzív keresés
                if (!empty($part['parts'])) {
                    $attachments = array_merge($attachments, self::getAttachmentParts($part));
                }
            }
        }
        return $attachments;
    }
}
