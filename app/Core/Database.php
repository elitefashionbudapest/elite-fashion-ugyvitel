<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES    => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND  => "SET NAMES utf8mb4 COLLATE utf8mb4_hungarian_ci",
                ]);
            } catch (PDOException $e) {
                $appConfig = require __DIR__ . '/../../config/app.php';
                if ($appConfig['debug']) {
                    die('Adatbázis kapcsolódási hiba: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
                }
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(503);
                die('A szolgáltatás átmenetileg nem elérhető. Kérjük, próbálja újra később.');
            }
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
