<?php

namespace App\Services;

use App\Core\Database;
use App\Models\{CompanySetting, Supplier};

class InvoiceEmailProcessor
{
    private string $apiKey;
    private string $companyName;
    private array $companyNameVariants;
    private string $companyTaxNumber;
    private string $companyEuVat;
    private GmailApiClient $gmail;
    private array $log = [];

    public function __construct()
    {
        $this->apiKey = CompanySetting::get('anthropic_api_key', '');
        $this->companyName = CompanySetting::get('company_name', '');
        $this->companyTaxNumber = CompanySetting::get('company_tax_number', '');
        $this->companyEuVat = CompanySetting::get('company_eu_vat', '');

        $variants = CompanySetting::get('company_name_variants', '');
        $this->companyNameVariants = array_filter(array_map('trim', explode(',', $variants)));
        if ($this->companyName) {
            array_unshift($this->companyNameVariants, $this->companyName);
        }

        $this->gmail = new GmailApiClient();
    }

    public function process(): array
    {
        if (!$this->gmail->isConnected()) {
            $this->log[] = ['status' => 'error', 'message' => 'Gmail nincs csatlakoztatva. Menj a Cégbeállításokba és csatlakoztasd!'];
            return $this->log;
        }
        if (!$this->apiKey) {
            $this->log[] = ['status' => 'error', 'message' => 'Anthropic API kulcs hiányzik!'];
            return $this->log;
        }

        $emails = $this->gmail->getRecentEmails();
        if (empty($emails)) {
            $this->log[] = ['status' => 'info', 'message' => 'Nincs új email tegnap óta'];
            return $this->log;
        }

        $this->log[] = ['status' => 'info', 'message' => count($emails) . ' email találva'];
        $db = Database::getInstance();

        foreach ($emails as $emailRef) {
            $msgId = $emailRef['id'];

            // Már feldolgoztuk?
            $stmt = $db->prepare('SELECT id FROM invoice_email_log WHERE email_uid = :uid');
            $stmt->execute(['uid' => $msgId]);
            if ($stmt->fetch()) continue;

            $msg = $this->gmail->getMessage($msgId);
            if (!$msg) continue;

            $headers = $msg['payload']['headers'] ?? [];
            $subject = GmailApiClient::getHeader($headers, 'Subject');
            $from = GmailApiClient::getHeader($headers, 'From');
            $date = GmailApiClient::getHeader($headers, 'Date');
            $dateFormatted = date('Y-m-d H:i:s', strtotime($date) ?: time());

            // Fizetési felszólítás kiszűrése
            $subjectLower = mb_strtolower($subject);
            if (str_contains($subjectLower, 'felszólít') || str_contains($subjectLower, 'fizetési emlékeztet') ||
                str_contains($subjectLower, 'overdue') || str_contains($subjectLower, 'payment reminder')) {
                $this->logEmail($db, $msgId, $subject, $from, $dateFormatted, 'skipped', null, 'Fizetési felszólítás kihagyva');
                continue;
            }

            // Csatolmányok
            $attachmentParts = GmailApiClient::getAttachmentParts($msg['payload']);
            $processedAny = false;

            foreach ($attachmentParts as $att) {
                $data = $this->gmail->getAttachment($msgId, $att['attachmentId']);
                if (!$data || strlen($data) < 500) continue;

                $extMap = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                $ext = $extMap[$att['mime']] ?? 'bin';

                $this->processFile($db, $msgId, $subject, $from, $dateFormatted, $data, $att['mime'], $ext);
                $processedAny = true;
            }

            // Ha nincs csatolmány, keressünk linket
            if (!$processedAny) {
                $body = GmailApiClient::getBody($msg['payload']);
                if ($body) {
                    $links = $this->extractInvoiceLinks($body);
                    foreach ($links as $link) {
                        $downloaded = $this->downloadFromLink($link);
                        if ($downloaded) {
                            $this->processFile($db, $msgId, $subject, $from, $dateFormatted, $downloaded['data'], $downloaded['mime'], $downloaded['ext']);
                            $processedAny = true;
                        }
                    }
                }
            }

            if (!$processedAny) {
                $this->logEmail($db, $msgId, $subject, $from, $dateFormatted, 'skipped', null, 'Nincs csatolmány vagy letölthető számla');
            }
        }

        return $this->log;
    }

    private function processFile(\PDO $db, string $msgId, string $subject, string $from, string $date, string $data, string $mime, string $ext): void
    {
        // Fájl mentése
        $uploadDir = __DIR__ . '/../../public/uploads/invoices/auto/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        file_put_contents($uploadDir . $filename, $data);
        $imagePath = '/uploads/invoices/auto/' . $filename;

        $uid = $msgId . '_' . $filename;

        // Claude API feldolgozás
        $result = $this->analyzeWithClaude($data, $mime);

        if (!$result) {
            $this->logEmail($db, $uid, $subject, $from, $date, 'error', null, 'AI feldolgozás sikertelen');
            return;
        }

        if (!($result['is_invoice'] ?? false)) {
            $this->logEmail($db, $uid, $subject, $from, $date, 'not_invoice', null, 'Nem számla: ' . ($result['reason'] ?? ''));
            return;
        }

        if (!$this->isForOurCompany($result)) {
            $this->logEmail($db, $uid, $subject, $from, $date, 'skipped', null, 'Nem nekünk szól: ' . ($result['buyer_name'] ?? 'ismeretlen'));
            return;
        }

        // Duplikátum ellenőrzés
        $invoiceNumber = $result['invoice_number'] ?? '';
        if ($invoiceNumber) {
            $stmt = $db->prepare('SELECT id FROM invoices WHERE invoice_number = :num');
            $stmt->execute(['num' => $invoiceNumber]);
            if ($stmt->fetch()) {
                $this->logEmail($db, $uid, $subject, $from, $date, 'duplicate', null, 'Már létezik: ' . $invoiceNumber);
                return;
            }
        }

        // Rögzítés
        $supplierName = $result['supplier_name'] ?? $from;
        $supplierId = Supplier::findOrCreate($supplierName);

        $netAmount = (float)($result['net_amount'] ?? 0);
        $grossAmount = (float)($result['gross_amount'] ?? 0);
        if ($grossAmount <= 0 && $netAmount > 0) $grossAmount = $netAmount;
        if ($netAmount <= 0 && $grossAmount > 0) $netAmount = $grossAmount;

        $needsReview = ($result['confidence'] ?? 'low') !== 'high';
        $currency = strtoupper($result['currency'] ?? 'HUF');
        if (!in_array($currency, ['HUF', 'EUR', 'USD', 'GBP'])) $currency = 'HUF';

        $stmt = $db->prepare(
            'INSERT INTO invoices (store_id, supplier_id, invoice_number, net_amount, amount, currency, invoice_date, due_date, payment_method, notes, recorded_by, needs_review, auto_imported, image_path)
             VALUES (NULL, :sup, :num, :net, :gross, :cur, :idate, :ddate, :method, :notes, 1, :review, 1, :img)'
        );
        $stmt->execute([
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

        $this->logEmail($db, $uid, $subject, $from, $date, 'processed', $newId, 'Rögzítve: ' . $invoiceNumber);
        $this->log[] = ['status' => 'success', 'message' => "Számla: {$supplierName} — {$invoiceNumber} — " . number_format($grossAmount, 0, ',', ' ') . " {$currency}"];
    }

    private function analyzeWithClaude(string $fileData, string $mime): ?array
    {
        $isImage = str_starts_with($mime, 'image/');

        $messages = [['role' => 'user', 'content' => []]];

        if ($isImage) {
            $messages[0]['content'][] = [
                'type' => 'image',
                'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => base64_encode($fileData)],
            ];
        } elseif ($mime === 'application/pdf') {
            $messages[0]['content'][] = [
                'type' => 'document',
                'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($fileData)],
            ];
        }

        $prompt = "Elemezd ezt a dokumentumot. Válaszolj KIZÁRÓLAG JSON formátumban.\n\n";
        $prompt .= "Ha számla/invoice:\n";
        $prompt .= '{"is_invoice":true,"supplier_name":"...","invoice_number":"...","net_amount":0,"gross_amount":0,"currency":"HUF","invoice_date":"YYYY-MM-DD","due_date":"YYYY-MM-DD","payment_method":"atutalas","buyer_name":"...","buyer_tax_number":"...","confidence":"high/medium/low"}' . "\n\n";
        $prompt .= "Ha NEM számla:\n";
        $prompt .= '{"is_invoice":false,"reason":"..."}' . "\n\n";
        $prompt .= "payment_method: keszpenz, atutalas, kartya, utanvet\n";
        $prompt .= "confidence: high=minden olvasható, medium=néhány bizonytalan, low=sok hiányzik\n";

        $messages[0]['content'][] = ['type' => 'text', 'text' => $prompt];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
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

        if (preg_match('/\{.*\}/s', $text, $match)) {
            return json_decode($match[0], true);
        }
        return null;
    }

    private function isForOurCompany(array $result): bool
    {
        $buyerName = mb_strtolower($result['buyer_name'] ?? '');
        $buyerTax = $result['buyer_tax_number'] ?? '';

        if ($this->companyTaxNumber && $buyerTax && str_contains(str_replace('-', '', $buyerTax), str_replace('-', '', $this->companyTaxNumber))) {
            return true;
        }
        if ($this->companyEuVat && $buyerTax && str_contains(strtoupper($buyerTax), strtoupper($this->companyEuVat))) {
            return true;
        }
        foreach ($this->companyNameVariants as $variant) {
            if (str_contains($buyerName, mb_strtolower($variant))) return true;
        }
        if (empty($buyerName)) return true;
        return false;
    }

    private function extractInvoiceLinks(string $body): array
    {
        $links = [];
        if (preg_match_all('/https?:\/\/[^\s<>"\']+/i', $body, $matches)) {
            foreach ($matches[0] as $url) {
                $u = strtolower($url);
                if (str_contains($u, 'invoice') || str_contains($u, 'szamla') || str_contains($u, 'receipt') ||
                    str_contains($u, 'billing') || str_contains($u, 'download') || str_contains($u, '.pdf')) {
                    $links[] = $url;
                }
            }
        }
        return array_slice($links, 0, 3);
    }

    private function downloadFromLink(string $url): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);
        $data = curl_exec($ch);
        $mime = explode(';', curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?? '')[0];
        curl_close($ch);

        if (!$data || strlen($data) < 500) return null;
        $extMap = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (isset($extMap[$mime])) return ['data' => $data, 'mime' => $mime, 'ext' => $extMap[$mime]];
        if (str_starts_with($data, '%PDF')) return ['data' => $data, 'mime' => 'application/pdf', 'ext' => 'pdf'];
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
