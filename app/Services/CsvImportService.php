<?php

namespace App\Services;

class CsvImportService
{
    /**
     * OTP CSV fájl feldolgozása
     */
    public static function parseCsv(string $content): array
    {
        // Encoding fix (OTP CSV gyakran ISO-8859-2 vagy Windows-1250)
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-2');
        }
        // BOM eltávolítás
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $rows = [];
        $lines = explode("\n", str_replace("\r\n", "\n", $content));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $cols = str_getcsv($line, ';', '"');
            if (count($cols) < 13) continue;

            // Összeg (col 2) - ha nincs szám, skip
            $amount = (float)str_replace([' ', ','], ['', '.'], $cols[2] ?? '0');
            if ($amount == 0) continue;

            $direction = trim($cols[1] ?? '');
            if (!in_array($direction, ['T', 'J'])) continue;

            // Dátum konverzió: YYYYMMDD → YYYY-MM-DD
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
     * Típus tipp a CSV sor alapján
     */
    public static function suggestType(array $row): ?string
    {
        $partner = mb_strtolower($row['partner_name'] ?? '');
        $desc = mb_strtolower($row['description'] ?? '');
        $csvType = mb_strtolower($row['csv_type'] ?? '');
        $direction = $row['direction'] ?? 'T';

        // NAV → adó
        if (str_contains($partner, 'nav ') || str_contains($partner, 'nav-')) {
            return 'ado_kifizetes';
        }

        // Kártyás elfogadó elszámolás → kártyás beérkezés
        if (str_contains($partner, 'kartya') || str_contains($partner, 'kártya') ||
            str_contains($desc, 'kartya elfogado') || str_contains($desc, 'kártya elfogadó') ||
            str_contains($desc, 'elszamolas a kartya')) {
            if ($direction === 'J') return 'kartya_beerkezes';
        }

        // Banki díjak
        if (str_contains($csvType, 'költség') || str_contains($csvType, 'koltseg') ||
            str_contains($csvType, 'zárlati') || str_contains($csvType, 'zarlati') ||
            str_contains($csvType, 'különdíj') || str_contains($csvType, 'kulondij') ||
            str_contains($csvType, 'kp.felv') || str_contains($csvType, 'befiz. díj') ||
            str_contains($csvType, 'időszakos') || str_contains($csvType, 'idoszakos')) {
            return 'banki_jutalek';
        }

        // PayPal / Facebook / Google → szolgáltató
        if (str_contains($partner, 'paypal') || str_contains($partner, 'facebook') ||
            str_contains($partner, 'google') || str_contains($partner, 'tiktok')) {
            return 'szolgaltato_levon';
        }

        // Bejövő → valószínűleg kártyás vagy átutalás
        if ($direction === 'J') {
            return 'kartya_beerkezes';
        }

        // Kimenő ismeretlen → szolgáltató
        if ($direction === 'T' && !empty($partner)) {
            return 'szolgaltato_levon';
        }

        return null;
    }
}
