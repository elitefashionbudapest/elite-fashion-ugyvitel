<?php
/**
 * Elite Fashion Deploy Script
 * Használat: php deploy.php
 *
 * FTP-vel feltölti a módosított fájlokat a szerverre.
 * A .deploy fájlból olvassa az FTP adatokat.
 */

// Színek a terminálban
function green(string $s): string { return "\033[32m{$s}\033[0m"; }
function red(string $s): string { return "\033[31m{$s}\033[0m"; }
function yellow(string $s): string { return "\033[33m{$s}\033[0m"; }

$root = __DIR__;
$deployFile = $root . '/.deploy';

if (!file_exists($deployFile)) {
    echo red("Hiba: .deploy fájl nem található!\n");
    exit(1);
}

// FTP adatok betöltése
$config = [];
foreach (file($deployFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_contains($line, '=')) {
        [$key, $value] = explode('=', $line, 2);
        $config[trim($key)] = trim($value);
    }
}

$host = $config['FTP_HOST'] ?? '';
$user = $config['FTP_USER'] ?? '';
$pass = $config['FTP_PASS'] ?? '';
$remotePath = rtrim($config['FTP_PATH'] ?? '/public_html', '/');

echo yellow("═══════════════════════════════════════\n");
echo yellow("  Elite Fashion Deploy\n");
echo yellow("═══════════════════════════════════════\n\n");

// Kizárt mappák/fájlok
$excludes = [
    '.git', '.deploy', '.env', '.claude',
    'Formok', 'Képkivágás.JPG',
    'storage/backups', 'storage/cache',
    'public/uploads',
    'deploy.php',
];

// FTP kapcsolódás
echo "Kapcsolódás: {$host}...\n";
$ftp = ftp_connect($host);
if (!$ftp) {
    echo red("Hiba: Nem sikerült csatlakozni!\n");
    exit(1);
}

if (!ftp_login($ftp, $user, $pass)) {
    echo red("Hiba: Bejelentkezés sikertelen!\n");
    ftp_close($ftp);
    exit(1);
}

ftp_pasv($ftp, true);
echo green("Csatlakozva!\n\n");

// Fájlok gyűjtése
$files = [];
$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iter as $file) {
    if (!$file->isFile()) continue;

    $relative = str_replace('\\', '/', substr($file->getRealPath(), strlen(realpath($root)) + 1));

    // Kizárások
    $skip = false;
    foreach ($excludes as $exc) {
        if (str_starts_with($relative, $exc)) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;

    $files[] = $relative;
}

echo "Feltöltendő fájlok: " . count($files) . "\n\n";

// Feltöltés
$uploaded = 0;
$errors = 0;

foreach ($files as $relative) {
    $localPath = $root . '/' . $relative;
    $remoteFile = $remotePath . '/' . $relative;
    $remoteDir = dirname($remoteFile);

    // Mappa létrehozása (rekurzív)
    ftpMkdirRecursive($ftp, $remoteDir);

    if (ftp_put($ftp, $remoteFile, $localPath, FTP_BINARY)) {
        $uploaded++;
        // Progressz kiírás minden 10. fájlnál
        if ($uploaded % 10 === 0 || $uploaded === count($files)) {
            echo "\r  " . green("▓") . " {$uploaded}/" . count($files) . " ({$relative})";
        }
    } else {
        $errors++;
        echo "\n  " . red("✗ {$relative}");
    }
}

ftp_close($ftp);

echo "\n\n" . yellow("═══════════════════════════════════════\n");
echo green("  Kész! {$uploaded} fájl feltöltve.\n");
if ($errors > 0) echo red("  {$errors} hiba történt.\n");
echo yellow("═══════════════════════════════════════\n");

function ftpMkdirRecursive($ftp, string $dir): void
{
    if (@ftp_chdir($ftp, $dir)) {
        ftp_chdir($ftp, '/');
        return;
    }

    $parts = explode('/', $dir);
    $path = '';
    foreach ($parts as $part) {
        if ($part === '') continue;
        $path .= '/' . $part;
        if (!@ftp_chdir($ftp, $path)) {
            @ftp_mkdir($ftp, $path);
        }
    }
    ftp_chdir($ftp, '/');
}
