<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog, Database};

class BackupController
{
    public function index(): void
    {
        Middleware::owner();

        $backupDir = __DIR__ . '/../../storage/backups/';
        $backups = [];

        if (is_dir($backupDir)) {
            $files = glob($backupDir . '*.zip');
            rsort($files);
            foreach ($files as $f) {
                $backups[] = [
                    'filename' => basename($f),
                    'size'     => filesize($f),
                    'date'     => date('Y-m-d H:i:s', filemtime($f)),
                ];
            }
        }

        view('layouts/app', [
            'content' => 'backup/index',
            'data' => [
                'pageTitle' => 'Adatmentés',
                'activeTab' => 'backup',
                'backups'   => $backups,
            ]
        ]);
    }

    public function create(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $backupDir = __DIR__ . '/../../storage/backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_His');
        $zipFilename = 'backup_' . $timestamp . '.zip';
        $zipPath = $backupDir . $zipFilename;

        // 1. Adatbázis dump készítése
        $sqlDump = $this->createDatabaseDump();

        // 2. ZIP létrehozása: DB dump + feltöltött fájlok
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            set_flash('error', 'Nem sikerült a ZIP fájl létrehozása.');
            redirect('/backup');
        }

        // DB dump hozzáadása
        $zip->addFromString('database.sql', $sqlDump);

        // Teljes projekt mappa (kivéve: vendor, storage/backups, .git)
        $projectDir = realpath(__DIR__ . '/../../');
        $this->addDirectoryToZip($zip, $projectDir, 'project/', [
            realpath($backupDir),
            realpath($projectDir . '/vendor'),
            realpath($projectDir . '/.git'),
            realpath($projectDir . '/storage/cache'),
        ]);

        $zip->close();

        AuditLog::log('create', 'backup', null, null, [
            'filename' => $zipFilename,
            'size'     => filesize($zipPath),
        ]);
        set_flash('success', 'Teljes adatmentés elkészült: ' . e($zipFilename));
        redirect('/backup');
    }

    public function download(string $filename): void
    {
        Middleware::owner();

        $filename = basename($filename);
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{6}\.zip$/', $filename)) {
            set_flash('error', 'Érvénytelen fájlnév.');
            redirect('/backup');
        }

        $filepath = __DIR__ . '/../../storage/backups/' . $filename;
        if (!file_exists($filepath)) {
            set_flash('error', 'A mentés nem található.');
            redirect('/backup');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    public function destroy(string $filename): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $filename = basename($filename);
        $filepath = __DIR__ . '/../../storage/backups/' . $filename;

        if (file_exists($filepath) && preg_match('/^backup_.*\.(zip|sql)$/', $filename)) {
            unlink($filepath);
            AuditLog::log('delete', 'backup', null, null, ['filename' => $filename]);
            set_flash('success', 'Mentés törölve.');
        }
        redirect('/backup');
    }

    private function createDatabaseDump(): string
    {
        $db = Database::getInstance();
        $tables = $db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

        $sql = "-- Elite Fashion adatbázis mentés\n";
        $sql .= "-- Dátum: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Készítette: " . e(Auth::user()['name'] ?? 'Ismeretlen') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\nSET NAMES utf8mb4;\n\n";

        foreach ($tables as $table) {
            $createStmt = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createStmt['Create Table'] . ";\n\n";

            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $colList = '`' . implode('`, `', $columns) . '`';

                foreach (array_chunk($rows, 100) as $chunk) {
                    $sql .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";
                    $values = [];
                    foreach ($chunk as $row) {
                        $vals = [];
                        foreach ($row as $val) {
                            $vals[] = $val === null ? 'NULL' : $db->quote($val);
                        }
                        $values[] = '(' . implode(', ', $vals) . ')';
                    }
                    $sql .= implode(",\n", $values) . ";\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        return $sql;
    }

    private function addDirectoryToZip(\ZipArchive $zip, string $dir, string $prefix, array $excludeDirs = []): void
    {
        $excludeDirs = array_filter($excludeDirs);
        $dir = realpath($dir);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isFile()) continue;

            $filePath = $file->getRealPath();

            // Kizárt mappák ellenőrzése
            $skip = false;
            foreach ($excludeDirs as $exc) {
                if ($exc && str_starts_with($filePath, $exc)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            $relativePath = $prefix . substr($filePath, strlen($dir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);
            $zip->addFile($filePath, $relativePath);
        }
    }
}
