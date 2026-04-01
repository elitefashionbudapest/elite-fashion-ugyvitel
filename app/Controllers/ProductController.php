<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\Product;

class ProductController
{
    /**
     * Terméklista oldal (feltöltés + aktuális lista)
     */
    public function index(): void
    {
        Middleware::owner();

        $productCount = Product::count();
        $products = Product::all();

        view('layouts/app', [
            'content' => 'products/index',
            'data' => [
                'pageTitle'    => 'Terméklista',
                'activeTab'    => 'termekek',
                'productCount' => $productCount,
                'products'     => $products,
            ]
        ]);
    }

    /**
     * CSV feltöltés és feldolgozás
     */
    public function upload(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            set_flash('error', 'Hiba a fájl feltöltésekor. Kérlek válassz egy CSV fájlt.');
            redirect('/products');
            return;
        }

        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            set_flash('error', 'Csak CSV fájl tölthető fel.');
            redirect('/products');
            return;
        }

        $content = file_get_contents($file['tmp_name']);

        // BOM eltávolítása ha van
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $lines = explode("\n", $content);
        if (count($lines) < 2) {
            set_flash('error', 'A CSV fájl üres vagy nem tartalmaz adatokat.');
            redirect('/products');
            return;
        }

        // Fejléc sor elemzése - elválasztó megállapítása
        $headerLine = $lines[0];
        $separator = str_contains($headerLine, ';') ? ';' : ',';

        // Régi termékek törlése, új importálás
        $replaceAll = isset($_POST['replace_all']) && $_POST['replace_all'] === '1';
        if ($replaceAll) {
            Product::truncate();
        }

        $imported = 0;
        $skipped = 0;

        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $cols = str_getcsv($line, $separator, '"');

            // Minimum: Terméknév (0), Termék kód (1), Vonalkód (2)
            if (count($cols) < 3 || empty(trim($cols[0])) || empty(trim($cols[2]))) {
                $skipped++;
                continue;
            }

            $name       = trim($cols[0]);
            $sku        = trim($cols[1]);
            $barcode    = trim($cols[2]);
            $type       = isset($cols[3]) ? trim($cols[3]) : null;
            $netPrice   = isset($cols[6]) ? self::parseHungarianNumber($cols[6]) : 0;
            $vatRate    = isset($cols[7]) ? trim($cols[7]) : null;
            $grossPrice = isset($cols[8]) ? self::parseHungarianNumber($cols[8]) : 0;

            Product::upsert([
                'name'         => $name,
                'sku'          => $sku,
                'barcode'      => $barcode,
                'product_type' => $type,
                'net_price'    => $netPrice,
                'vat_rate'     => $vatRate,
                'gross_price'  => $grossPrice,
            ]);

            $imported++;
        }

        AuditLog::log('import', 'products', null, null, [
            'imported' => $imported,
            'skipped'  => $skipped,
            'replace'  => $replaceAll,
        ]);

        set_flash('success', "Terméklista importálva: {$imported} termék betöltve" . ($skipped > 0 ? ", {$skipped} sor kihagyva." : '.'));
        redirect('/products');
    }

    /**
     * Magyar szám formátum kezelése (pl. "4716,54" → 4716.54)
     */
    private static function parseHungarianNumber(string $value): float
    {
        $value = trim($value, ' "\'');
        $value = str_replace('.', '', $value);   // ezres elválasztó eltávolítása
        $value = str_replace(',', '.', $value);  // tizedes vessző → pont
        return (float)$value;
    }
}
