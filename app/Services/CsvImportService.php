<?php

namespace App\Services;

class CsvImportService
{
    /**
     * OTP CSV fájl feldolgozása
     */
    public static function parseOtpCsv(string $content): array
    {
        // Encoding fix (OTP CSV gyakran ISO-8859-2 vagy Windows-1250)
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-2');
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $rows = [];
        $lines = explode("\n", str_replace("\r\n", "\n", $content));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $cols = str_getcsv($line, ';', '"');
            if (count($cols) < 13) continue;

            $amount = (float)str_replace([' ', ','], ['', '.'], $cols[2] ?? '0');
            if ($amount == 0) continue;

            $direction = trim($cols[1] ?? '');
            if (!in_array($direction, ['T', 'J'])) continue;

            $bookingDate = trim($cols[4] ?? '');
            if (strlen($bookingDate) === 8) {
                $bookingDate = substr($bookingDate, 0, 4) . '-' . substr($bookingDate, 4, 2) . '-' . substr($bookingDate, 6, 2);
            } else {
                $bookingDate = date('Y-m-d');
            }

            $partnerName = trim($cols[8] ?? '');
            $description = trim($cols[9] ?? '');
            $reference = trim($cols[10] ?? '');
            $csvType = trim($cols[12] ?? '');

            $rows[] = [
                'direction'    => $direction,
                'amount'       => abs($amount),
                'currency'     => trim($cols[3] ?? 'HUF'),
                'booking_date' => $bookingDate,
                'partner_name' => $partnerName,
                'description'  => $description,
                'reference'    => $reference,
                'csv_type'     => $csvType,
            ];
        }

        return $rows;
    }

    /**
     * CIB Excel (.xls/.xlsx) fájl feldolgozása
     */
    public static function parseCibExcel(string $filePath): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $allRows = $sheet->toArray();

        $rows = [];
        $dataStarted = false;

        foreach ($allRows as $excelRow) {
            // Fejléc sor keresése
            if (!$dataStarted) {
                if (isset($excelRow[0]) && trim($excelRow[0]) === 'DÁTUM') {
                    $dataStarted = true;
                }
                continue;
            }

            // Üres sor skip
            $date = trim($excelRow[0] ?? '');
            if (empty($date)) continue;

            // Dátum: "4/1/2026" vagy "3/31/2026" → YYYY-MM-DD
            $bookingDate = self::parseCibDate($date);
            if (!$bookingDate) continue;

            $txType = trim($excelRow[1] ?? '');
            $comment = trim($excelRow[2] ?? '');
            $amountStr = trim($excelRow[3] ?? '0');
            $currency = trim($excelRow[4] ?? 'HUF');

            // Összeg: "621,375.52 " vagy "-9,370.00 "
            $amount = (float)str_replace([' ', ','], ['', ''], $amountStr);
            if ($amount == 0) continue;

            $direction = $amount >= 0 ? 'J' : 'T';
            $amount = abs($amount);

            // Közlemény feldolgozás (többsoros)
            $commentLines = explode("\n", $comment);
            $partnerName = '';
            $description = $comment;

            // NAV keresés a közleményben
            foreach ($commentLines as $cl) {
                $cl = trim($cl);
                if (str_starts_with($cl, 'NAV ')) {
                    $partnerName = $cl;
                }
                // Kereskedői elfogadás partner
                if (preg_match('/^\d+ .+/', $cl) && str_contains($comment, 'elfogadás')) {
                    $partnerName = preg_replace('/^\d+\s*/', '', $cl);
                }
            }

            $rows[] = [
                'direction'    => $direction,
                'amount'       => $amount,
                'currency'     => $currency,
                'booking_date' => $bookingDate,
                'partner_name' => $partnerName,
                'description'  => str_replace("\n", ' | ', $comment),
                'reference'    => '',
                'csv_type'     => $txType,
            ];
        }

        return $rows;
    }

    /**
     * CIB dátum formátum: "4/1/2026" vagy "3/31/2026" → "2026-04-01"
     */
    private static function parseCibDate(string $date): ?string
    {
        // M/D/YYYY formátum
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
        }
        // YYYY.MM.DD formátum
        if (preg_match('#^(\d{4})\.(\d{2})\.(\d{2})$#', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        return null;
    }

    /**
     * Fájl típus felismerés és feldolgozás
     */
    public static function parseFile(string $filePath, string $originalName, ?string $content = null): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'txt') {
            $content = $content ?? file_get_contents($filePath);
            return self::parseOtpCsv($content);
        }

        if ($ext === 'xls' || $ext === 'xlsx') {
            return self::parseCibExcel($filePath);
        }

        return [];
    }

    /**
     * Típus tipp a sor alapján
     */
    public static function suggestType(array $row): ?string
    {
        $partner = mb_strtolower($row['partner_name'] ?? '');
        $desc = mb_strtolower($row['description'] ?? '');
        $csvType = mb_strtolower($row['csv_type'] ?? '');
        $direction = $row['direction'] ?? 'T';

        // NAV → adó
        if (str_contains($partner, 'nav ') || str_contains($partner, 'nav-') ||
            str_contains($desc, 'nav ') || str_contains($desc, 'nav-')) {
            return 'ado_kifizetes';
        }

        // Kártyás elfogadó elszámolás → kártyás beérkezés
        if (str_contains($desc, 'kereskedői elfogadás') || str_contains($desc, 'kereskedoi elfogadas') ||
            str_contains($desc, 'kartya elfogado') || str_contains($desc, 'kártya elfogadó') ||
            str_contains($desc, 'elszamolas a kartya')) {
            if ($direction === 'J') return 'kartya_beerkezes';
        }

        // Banki díjak
        if (str_contains($csvType, 'díj') || str_contains($csvType, 'kamat') ||
            str_contains($csvType, 'költség') || str_contains($csvType, 'koltseg') ||
            str_contains($csvType, 'zárlati') || str_contains($csvType, 'zarlati') ||
            str_contains($csvType, 'különdíj') || str_contains($csvType, 'kulondij') ||
            str_contains($csvType, 'kp.felv') || str_contains($csvType, 'befiz. díj') ||
            str_contains($csvType, 'időszakos') || str_contains($csvType, 'idoszakos') ||
            str_contains($desc, 'számlavezetési díj') || str_contains($desc, 'pénzforgalmi díj')) {
            return 'banki_jutalek';
        }

        // PayPal / Facebook / Google → szolgáltató
        if (str_contains($partner, 'paypal') || str_contains($partner, 'facebook') ||
            str_contains($partner, 'google') || str_contains($partner, 'tiktok') ||
            str_contains($desc, 'paypal') || str_contains($desc, 'facebook') ||
            str_contains($desc, 'google') || str_contains($desc, 'tiktok')) {
            return 'szolgaltato_levon';
        }

        // Bejövő → valószínűleg kártyás
        if ($direction === 'J') {
            return 'kartya_beerkezes';
        }

        // Kimenő ismeretlen → szolgáltató
        if ($direction === 'T') {
            return 'szolgaltato_levon';
        }

        return null;
    }
}
