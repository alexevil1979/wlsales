<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $name = Env::get('DB_NAME', 'wlsales');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '+03:00'");
        } catch (PDOException $e) {
            $msg = 'Ошибка подключения к базе данных.';
            if (PHP_SAPI === 'cli' || Env::bool('APP_DEBUG')) {
                $msg .= ' ' . $e->getMessage()
                    . ' (host=' . $host . '; db=' . $name . '; user=' . $user . ')';
            }
            if (Env::bool('APP_DEBUG') && PHP_SAPI !== 'cli') {
                throw $e;
            }
            if (PHP_SAPI !== 'cli') {
                http_response_code(500);
            }
            echo $msg . (PHP_SAPI === 'cli' ? "\n" : '');
            exit(1);
        }

        return self::$pdo;
    }
}
