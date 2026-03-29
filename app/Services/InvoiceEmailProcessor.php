<?php

namespace App\Services;

use App\Core\Database;
use App\Models\{CompanySetting, Invoice, Supplier};

class InvoiceEmailProcessor
{
    private string $imapHost;
    private string $imapPort;
    private string $imapEmail;
    private string $imapPassword;
    private string $imapEncryption;
    private string $apiKey;
    private string $companyName;
    private array $companyNameVariants;
    private string $companyTaxNumber;
    private string $companyEuVat;

    private array $log = [];

    public function __construct()
    {
        $this->imapHost = CompanySetting::get('imap_host', '');
        $this->imapPort = CompanySetting::get('imap_port', '993');
        $this->imapEmail = CompanySetting::get('imap_email', '');
        $this->imapPassword = CompanySetting::get('imap_password', '');
        $this->imapEncryption = CompanySetting::get('imap_encryption', 'ssl');
        $this->apiKey = CompanySetting::get('anthropic_api_key', '');
        $this->companyName = CompanySetting::get('company_name', '');
        $this->companyTaxNumber = CompanySetting::get('company_tax_number', '');
        $this->companyEuVat = CompanySetting::get('company_eu_vat', '');

        $variants = CompanySetting::get('company_name_variants', '');
        $this->companyNameVariants = array_filter(array_map('trim', explode(',', $variants)));
        if ($this->companyName) {
            array_unshift($this->companyNameVariants, $this->companyName);
        }
    }

    public function process(): array
    {
        if (!$this->imapHost || !$this->imapEmail || !$this->imapPassword || !$this->apiKey) {
            $this->log[] = ['status' => 'error', 'message' => 'Hiányzó beállítások (IMAP vagy API kulcs)'];
            return $this->log;
        }

        // IMAP kapcsolódás
        $enc = $this->imapEncryption === 'none' ? '/novalidate-cert' : '/' . $this->imapEncryption;
        $mailbox = '{' . $this->imapHost . ':' . $this->imapPort . '/imap' . $enc . '}INBOX';

        $imap = @imap_open($mailbox, $this->imapEmail, $this->imapPassword);
        if (!$imap) {
            $this->log[] = ['status' => 'error', 'message' => 'IMAP kapcsolódás sikertelen: ' . imap_last_error()];
            return $this->log;
        }

        // Tegnap óta érkezett emailek
        $since = date('d-M-Y', strtotime('-1 day'));
        $emails = imap_search($imap, 'SINCE "' . $since . '"', SE_UID);

        if (!$emails) {
            $this->log[] = ['status' => 'info', 'message' => 'Nincs új email tegnap óta'];
            imap_close($imap);
            return $this->log;
        }

        $this->log[] = ['status' => 'info', 'message' => count($emails) . ' email találva'];

        $db = Database::getInstance();

        foreach ($emails as $uid) {
            $uidStr = (string)$uid;

            // Már feldolgoztuk?
            $stmt = $db->prepare('SELECT id FROM invoice_email_log WHERE email_uid = :uid');
            $stmt->execute(['uid' => $uidStr]);
            if ($stmt->fetch()) continue;

            $header = imap_fetchheader($imap, $uid, FT_UID);
            $headerInfo = imap_headerinfo($imap, imap_msgno($imap, $uid));
            $subject = isset($headerInfo->subject) ? imap_utf8($headerInfo->subject) : '(nincs tárgy)';
            $from = isset($headerInfo->from[0]) ? ($headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host) : '';
            $date = isset($headerInfo->date) ? date('Y-m-d H:i:s', strtotime($headerInfo->date)) : date('Y-m-d H:i:s');

            // Fizetési felszólítás kiszűrése
            $subjectLower = mb_strtolower($subject);
            if (str_contains($subjectLower, 'felszólít') || str_contains($subjectLower, 'fizetési emlékeztet') ||
                str_contains($subjectLower, 'overdue') || str_contains($subjectLower, 'reminder') ||
                str_contains($subjectLower, 'payment reminder')) {
                $this->logEmail($db, $uidStr, $subject, $from, $date, 'skipped', null, 'Fizetési felszólítás kihagyva');
                continue;
            }

            // Csatolmányok keresése
            $structure = imap_fetchstructure($imap, $uid, FT_UID);
            $attachments = $this->getAttachments($imap, $uid, $structure);

            // Ha nincs csatolmány, keressünk linket az email törzsben
            $body = $this->getEmailBody($imap, $uid);
            if (empty($attachments) && $body) {
                $links = $this->extractInvoiceLinks($body);
                foreach ($links as $link) {
                    $downloaded = $this->downloadFromLink($link);
                    if ($downloaded) {
                        $attachments[] = $downloaded;
                    }
                }
            }

            if (empty($attachments)) {
                $this->logEmail($db, $uidStr, $subject, $from, $date, 'skipped', null, 'Nincs csatolmány vagy letölthető számla');
                continue;
            }

            // Minden csatolmányt feldolgozunk
            foreach ($attachments as $attachment) {
                $this->processAttachment($db, $uidStr, $subject, $from, $date, $attachment);
            }
        }

        imap_close($imap);
        return $this->log;
    }

    private function processAttachment(\PDO $db, string $uid, string $subject, string $from, string $date, array $attachment): void
    {
        // Fájl mentése
        $uploadDir = __DIR__ . '/../../public/uploads/invoices/auto/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = bin2hex(random_bytes(16)) . '.' . $attachment['ext'];
        $filepath = $uploadDir . $filename;
        file_put_contents($filepath, $attachment['data']);
        $imagePath = '/uploads/invoices/auto/' . $filename;

        // Claude API-val feldolgozás
        $result = $this->analyzeWithClaude($attachment['data'], $attachment['mime'], $attachment['ext']);

        if (!$result) {
            $this->logEmail($db, $uid . '_' . $filename, $subject, $from, $date, 'error', null, 'AI feldolgozás sikertelen');
            return;
        }

        // Nem számla?
        if (!($result['is_invoice'] ?? false)) {
            $this->logEmail($db, $uid . '_' . $filename, $subject, $from, $date, 'not_invoice', null, 'Nem számla: ' . ($result['reason'] ?? ''));
            return;
        }

        // Ellenőrizzük hogy nekünk szól-e
        if (!$this->isForOurCompany($result)) {
            $this->logEmail($db, $uid . '_' . $filename, $subject, $from, $date, 'skipped', null, 'Nem nekünk szól: ' . ($result['buyer_name'] ?? 'ismeretlen'));
            return;
        }

        // Duplikátum ellenőrzés
        $invoiceNumber = $result['invoice_number'] ?? '';
        if ($invoiceNumber) {
            $stmt = $db->prepare('SELECT id FROM invoices WHERE invoice_number = :num');
            $stmt->execute(['num' => $invoiceNumber]);
            if ($stmt->fetch()) {
                $this->logEmail($db, $uid . '_' . $filename, $subject, $from, $date, 'duplicate', null, 'Már létezik: ' . $invoiceNumber);
                return;
            }
        }

        // Szállító keresés/létrehozás
        $supplierName = $result['supplier_name'] ?? $from;
        $supplierId = Supplier::findOrCreate($supplierName);

        // Számla rögzítése
        $netAmount = (float)($result['net_amount'] ?? 0);
        $grossAmount = (float)($result['gross_amount'] ?? 0);
        if ($grossAmount <= 0 && $netAmount > 0) $grossAmount = $netAmount;
        if ($netAmount <= 0 && $grossAmount > 0) $netAmount = $grossAmount;

        $needsReview = ($result['confidence'] ?? 'low') !== 'high';

        $currency = strtoupper($result['currency'] ?? 'HUF');
        if (!in_array($currency, ['HUF', 'EUR', 'USD', 'GBP'])) $currency = 'HUF';

        $invoiceId = $db->prepare(
            'INSERT INTO invoices (store_id, supplier_id, invoice_number, net_amount, amount, currency, invoice_date, due_date, payment_method, notes, recorded_by, needs_review, auto_imported, image_path)
             VALUES (NULL, :sup, :num, :net, :gross, :cur, :idate, :ddate, :method, :notes, 1, :review, 1, :img)'
        );
        $invoiceId->execute([
            'sup'    => $supplierId,
            'num'    => $invoiceNumber ?: 'AUTO-' . date('YmdHis'),
            'net'    => $netAmount,
            'gross'  => $grossAmount,
            'cur'    => $currency,
            'idate'  => $result['invoice_date'] ?? date('Y-m-d'),
            'ddate'  => $result['due_date'] ?? null,
            'method' => $result['payment_method'] ?? 'atutalas',
            'notes'  => $needsReview ? '⚠ Automatikusan importálva — ellenőrizd!' : 'Automatikusan importálva',
            'review' => (int)$needsReview,
            'img'    => $imagePath,
        ]);
        $newId = (int)$db->lastInsertId();

        $status = $needsReview ? 'processed' : 'processed';
        $note = $needsReview ? 'Rögzítve (ellenőrizd!): ' . $invoiceNumber : 'Rögzítve: ' . $invoiceNumber;
        $this->logEmail($db, $uid . '_' . $filename, $subject, $from, $date, $status, $newId, $note);
        $this->log[] = ['status' => 'success', 'message' => "Számla rögzítve: {$supplierName} — {$invoiceNumber} — " . ($grossAmount > 0 ? number_format($grossAmount, 0, ',', ' ') . ' ' . $currency : 'összeg ismeretlen')];
    }

    private function analyzeWithClaude(string $fileData, string $mime, string $ext): ?array
    {
        $isImage = str_starts_with($mime, 'image/');
        $isPdf = $mime === 'application/pdf';

        // PDF-ből szöveget kinyerjük
        $textContent = '';
        if ($isPdf) {
            $textContent = $this->extractPdfText($fileData);
        }

        $messages = [['role' => 'user', 'content' => []]];

        // Képet vagy PDF szöveget küldünk
        if ($isImage) {
            $messages[0]['content'][] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $mime,
                    'data' => base64_encode($fileData),
                ],
            ];
        }

        $prompt = "Elemezd ezt a dokumentumot. Válaszolj CSAK JSON formátumban, semmi mást ne írj.\n\n";
        $prompt .= "Ha ez egy számla/invoice, add vissza:\n";
        $prompt .= '{"is_invoice":true,"supplier_name":"...","invoice_number":"...","net_amount":0,"gross_amount":0,"currency":"HUF","invoice_date":"YYYY-MM-DD","due_date":"YYYY-MM-DD","payment_method":"atutalas","buyer_name":"...","buyer_tax_number":"...","confidence":"high/medium/low","reason":""}' . "\n\n";
        $prompt .= "Ha NEM számla (fizetési felszólítás, reklám, stb.):\n";
        $prompt .= '{"is_invoice":false,"reason":"..."}' . "\n\n";
        $prompt .= "payment_method értékek: keszpenz, atutalas, kartya, utanvet\n";
        $prompt .= "confidence: high = minden adat olvasható, medium = néhány adat bizonytalan, low = sok adat hiányzik/olvashatatlan\n";

        if ($textContent) {
            $prompt .= "\nA dokumentum szövege:\n" . mb_substr($textContent, 0, 4000);
        }

        $messages[0]['content'][] = ['type' => 'text', 'text' => $prompt];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 500,
                'messages' => $messages,
            ]),
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return null;

        $data = json_decode($response, true);
        $text = $data['content'][0]['text'] ?? '';

        // JSON kinyerése a válaszból
        if (preg_match('/\{[^{}]*\}/s', $text, $match)) {
            return json_decode($match[0], true);
        }

        return null;
    }

    private function extractPdfText(string $pdfData): string
    {
        // Egyszerű PDF szöveg kinyerés regex-szel
        $text = '';
        if (preg_match_all('/\((.*?)\)/s', $pdfData, $matches)) {
            $text = implode(' ', $matches[1]);
        }
        // Stream blokkok
        if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $pdfData, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if ($decoded && preg_match_all('/\((.*?)\)/s', $decoded, $m)) {
                    $text .= ' ' . implode(' ', $m[1]);
                }
            }
        }
        return trim($text);
    }

    private function isForOurCompany(array $result): bool
    {
        $buyerName = mb_strtolower($result['buyer_name'] ?? '');
        $buyerTax = $result['buyer_tax_number'] ?? '';

        // Adószám egyezés
        if ($this->companyTaxNumber && $buyerTax && str_contains(str_replace('-', '', $buyerTax), str_replace('-', '', $this->companyTaxNumber))) {
            return true;
        }

        // EU VAT szám egyezés
        if ($this->companyEuVat && $buyerTax && str_contains(strtoupper($buyerTax), strtoupper($this->companyEuVat))) {
            return true;
        }

        // Cégnév egyezés
        foreach ($this->companyNameVariants as $variant) {
            if (str_contains($buyerName, mb_strtolower($variant))) {
                return true;
            }
        }

        // Ha az AI nem tudta kiolvasni a vevőt, elfogadjuk (de review-ra jelöljük)
        if (empty($buyerName)) return true;

        return false;
    }

    private function getAttachments($imap, int $uid, $structure, string $partNum = ''): array
    {
        $attachments = [];
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $extMap = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

        if (isset($structure->parts)) {
            foreach ($structure->parts as $i => $part) {
                $pn = $partNum ? ($partNum . '.' . ($i + 1)) : (string)($i + 1);
                $mime = strtolower(($part->type == 0 ? 'text' : ($part->type == 3 ? 'application' : ($part->type == 5 ? 'image' : 'other'))) . '/' . strtolower($part->subtype));

                if (in_array($mime, $allowedMimes) && isset($part->disposition) && strtolower($part->disposition) === 'attachment') {
                    $data = imap_fetchbody($imap, $uid, $pn, FT_UID);
                    if ($part->encoding == 3) $data = base64_decode($data);
                    elseif ($part->encoding == 4) $data = quoted_printable_decode($data);

                    if (strlen($data) > 100) {
                        $attachments[] = ['data' => $data, 'mime' => $mime, 'ext' => $extMap[$mime] ?? 'bin'];
                    }
                }

                // Beágyazott részek
                if (isset($part->parts)) {
                    $attachments = array_merge($attachments, $this->getAttachments($imap, $uid, $part, $pn));
                }
            }
        }

        return $attachments;
    }

    private function getEmailBody($imap, int $uid): string
    {
        $body = imap_fetchbody($imap, $uid, '1', FT_UID);
        $body .= ' ' . imap_fetchbody($imap, $uid, '1.1', FT_UID);
        return strip_tags($body);
    }

    private function extractInvoiceLinks(string $body): array
    {
        $links = [];
        // URL-ek keresése amik számlára utalnak
        if (preg_match_all('/https?:\/\/[^\s<>"\']+/i', $body, $matches)) {
            foreach ($matches[0] as $url) {
                $urlLower = strtolower($url);
                if (str_contains($urlLower, 'invoice') || str_contains($urlLower, 'szamla') ||
                    str_contains($urlLower, 'receipt') || str_contains($urlLower, 'billing') ||
                    str_contains($urlLower, 'download') || str_contains($urlLower, '.pdf')) {
                    $links[] = $url;
                }
            }
        }
        return array_slice($links, 0, 3); // Max 3 link
    }

    private function downloadFromLink(string $url): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);
        $data = curl_exec($ch);
        $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if (!$data || strlen($data) < 500) return null;

        $mime = explode(';', $mime)[0] ?? '';
        $extMap = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

        if (isset($extMap[$mime])) {
            return ['data' => $data, 'mime' => $mime, 'ext' => $extMap[$mime]];
        }

        // Ha nem ismert MIME de PDF-re hasonlít
        if (str_starts_with($data, '%PDF')) {
            return ['data' => $data, 'mime' => 'application/pdf', 'ext' => 'pdf'];
        }

        return null;
    }

    private function logEmail(\PDO $db, string $uid, string $subject, string $from, string $date, string $status, ?int $invoiceId, ?string $notes): void
    {
        $stmt = $db->prepare(
            'INSERT INTO invoice_email_log (email_uid, email_subject, email_from, email_date, status, invoice_id, notes)
             VALUES (:uid, :subj, :from, :date, :status, :inv, :notes)
             ON DUPLICATE KEY UPDATE status = :status2, notes = :notes2'
        );
        $stmt->execute([
            'uid' => $uid, 'subj' => mb_substr($subject, 0, 500), 'from' => $from,
            'date' => $date, 'status' => $status, 'inv' => $invoiceId,
            'notes' => $notes, 'status2' => $status, 'notes2' => $notes,
        ]);
    }
}
