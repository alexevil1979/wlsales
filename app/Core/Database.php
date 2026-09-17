<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    /** @var array<string, bool> */
    private static array $tableCache = [];

    /** @var array<string, bool> */
    private static array $columnCache = [];

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

    public static function hasTable(string $table): bool
    {
        if (array_key_exists($table, self::$tableCache)) {
            return self::$tableCache[$table];
        }
        try {
            $st = self::pdo()->prepare(
                'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
            );
            $st->execute([$table]);
            self::$tableCache[$table] = (bool) $st->fetchColumn();
        } catch (\Throwable) {
            self::$tableCache[$table] = false;
        }
        return self::$tableCache[$table];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }
        try {
            $st = self::pdo()->prepare(
                'SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
            );
            $st->execute([$table, $column]);
            self::$columnCache[$key] = (bool) $st->fetchColumn();
        } catch (\Throwable) {
            self::$columnCache[$key] = false;
        }
        return self::$columnCache[$key];
    }
}
