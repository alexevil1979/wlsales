<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Setting
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    public static function get(string $key, string $default = ''): string
    {
        self::warm();
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)'
        );
        $st->execute([$key, $value]);
        self::$cache[$key] = $value;
    }

    /** @param array<string, string> $pairs */
    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $k => $v) {
            self::set($k, $v);
        }
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        self::warm();
        return self::$cache ?? [];
    }

    private static function warm(): void
    {
        if (self::$cache !== null) {
            return;
        }
        try {
            $rows = Database::pdo()->query('SELECT k, v FROM settings')->fetchAll();
            self::$cache = [];
            foreach ($rows as $row) {
                self::$cache[$row['k']] = (string) ($row['v'] ?? '');
            }
        } catch (\Throwable $e) {
            self::$cache = [];
        }
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
