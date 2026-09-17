<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Настройки платёжного шлюза из БД — как App\Models\PaymentGateway в fullvpnservice.
 */
final class PaymentGateway
{
    /** @var list<string> */
    public const INTERNAL_ONLY_CODES = [
        'telegram_admin_notify',
        'telegram_database_backup',
    ];

    public static function findById(int $id): ?array
    {
        if (!Database::hasTable('payment_gateways')) {
            return null;
        }
        $st = Database::pdo()->prepare('SELECT * FROM payment_gateways WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByCode(string $code): ?array
    {
        if (!Database::hasTable('payment_gateways')) {
            return null;
        }
        $st = Database::pdo()->prepare('SELECT * FROM payment_gateways WHERE code = ? LIMIT 1');
        $st->execute([strtolower(trim($code))]);
        $row = $st->fetch();
        return $row ? self::hydrate($row) : null;
    }

    /** @return list<array> */
    public static function all(bool $paymentOnly = true): array
    {
        if (!Database::hasTable('payment_gateways')) {
            return [];
        }
        $sql = 'SELECT * FROM payment_gateways';
        if ($paymentOnly) {
            $placeholders = implode(',', array_fill(0, count(self::INTERNAL_ONLY_CODES), '?'));
            $sql .= ' WHERE code NOT IN (' . $placeholders . ')';
            $st = Database::pdo()->prepare($sql . ' ORDER BY name ASC');
            $st->execute(self::INTERNAL_ONLY_CODES);
        } else {
            $st = Database::pdo()->query($sql . ' ORDER BY name ASC');
        }
        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[] = self::hydrate($row);
        }
        return $out;
    }

    /** @return list<array> */
    public static function enabledForAmount(float $amountRub): array
    {
        $out = [];
        foreach (self::all(true) as $gw) {
            if (!(bool) $gw['enabled']) {
                continue;
            }
            if ((float) $gw['min_amount_rub'] > $amountRub) {
                continue;
            }
            $out[] = $gw;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function update(int $id, string $name, bool $enabled, bool $testMode, float $minAmount, array $config): void
    {
        $existing = self::findById($id);
        if (!$existing) {
            return;
        }
        $merged = self::mergeConfig($existing['config'], $config);
        $st = Database::pdo()->prepare(
            'UPDATE payment_gateways SET name = ?, enabled = ?, test_mode = ?, min_amount_rub = ?, config = ?, updated_at = ? WHERE id = ?'
        );
        $st->execute([
            $name,
            $enabled ? 1 : 0,
            $testMode ? 1 : 0,
            round($minAmount, 2),
            json_encode($merged, JSON_UNESCAPED_UNICODE),
            now_dt(),
            $id,
        ]);
    }

    public static function setEnabled(int $id, bool $enabled): void
    {
        $st = Database::pdo()->prepare('UPDATE payment_gateways SET enabled = ?, updated_at = ? WHERE id = ?');
        $st->execute([$enabled ? 1 : 0, now_dt(), $id]);
    }

    public static function setTestMode(int $id, bool $testMode): void
    {
        $st = Database::pdo()->prepare('UPDATE payment_gateways SET test_mode = ?, updated_at = ? WHERE id = ?');
        $st->execute([$testMode ? 1 : 0, now_dt(), $id]);
    }

    /**
     * Список для админки (как Filament table): поиск, фильтры, legacy-коды скрыты по умолчанию.
     *
     * @return list<array>
     */
    public static function adminList(string $q = '', string $enabled = '', string $testMode = '', string $type = ''): array
    {
        /** @var list<string> $hiddenLegacy */
        $hiddenLegacy = ['yookassa', 'tinkoff', 'sber', 'robokassa'];
        $rows = self::all(false);
        $q = mb_strtolower(trim($q));
        $out = [];
        foreach ($rows as $row) {
            $code = strtolower((string) $row['code']);
            if ($type !== 'legacy' && in_array($code, $hiddenLegacy, true)) {
                continue;
            }
            if ($type === 'internal' && !in_array($code, self::INTERNAL_ONLY_CODES, true)) {
                continue;
            }
            if ($type === 'payment' && in_array($code, self::INTERNAL_ONLY_CODES, true)) {
                continue;
            }
            if ($enabled === '1' && !$row['enabled']) {
                continue;
            }
            if ($enabled === '0' && $row['enabled']) {
                continue;
            }
            if ($testMode === '1' && !$row['test_mode']) {
                continue;
            }
            if ($testMode === '0' && $row['test_mode']) {
                continue;
            }
            if ($q !== '') {
                $hay = mb_strtolower($code . ' ' . (string) $row['name'] . ' ' . (string) ($row['config']['boosty_page_slug'] ?? ''));
                if (!str_contains($hay, $q)) {
                    continue;
                }
            }
            $out[] = $row;
        }
        usort($out, static fn ($a, $b) => ((int) $b['id']) <=> ((int) $a['id']));
        return $out;
    }

    public static function create(string $code, string $name, bool $enabled = false, bool $testMode = true, array $config = []): int
    {
        $code = strtolower(trim($code));
        if ($code === '' || self::findByCode($code)) {
            throw new \RuntimeException('Код шлюза пуст или уже существует');
        }
        $st = Database::pdo()->prepare(
            'INSERT INTO payment_gateways (code, name, enabled, test_mode, min_amount_rub, config, created_at) VALUES (?,?,?,?,?,?,?)'
        );
        $st->execute([
            $code,
            $name,
            $enabled ? 1 : 0,
            $testMode ? 1 : 0,
            0,
            json_encode(self::cleanConfig($config), JSON_UNESCAPED_UNICODE),
            now_dt(),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function upsert(string $code, string $name, bool $enabled = false, bool $testMode = true, array $config = []): void
    {
        $code = strtolower(trim($code));
        $existing = self::findByCode($code);
        if ($existing) {
            return;
        }
        $st = Database::pdo()->prepare(
            'INSERT INTO payment_gateways (code, name, enabled, test_mode, min_amount_rub, config, created_at) VALUES (?,?,?,?,?,?,?)'
        );
        $st->execute([
            $code,
            $name,
            $enabled ? 1 : 0,
            $testMode ? 1 : 0,
            0,
            json_encode(self::cleanConfig($config), JSON_UNESCAPED_UNICODE),
            now_dt(),
        ]);
    }

    public static function cfg(array $gateway, string $key, mixed $default = null): mixed
    {
        $config = $gateway['config'] ?? [];
        if (!is_array($config)) {
            return $default;
        }
        return $config[$key] ?? $default;
    }

    public static function cfgString(array $gateway, string $key, string $default = ''): string
    {
        $v = self::cfg($gateway, $key, $default);
        return trim((string) $v);
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $incoming
     * @return array<string, mixed>
     */
    public static function mergeConfig(array $old, array $incoming): array
    {
        $secretKeys = ['secret', 'secret_key', 'api_key', 'api_token', 'api_private', 'password', 'api_password', 'certificate_password', 'ipn_secret', 'webhook_secret', 'webhook_token_value', 'secret_word_2', 'signature_key'];
        foreach ($incoming as $k => $v) {
            if (is_array($v)) {
                $old[$k] = $v;
                continue;
            }
            $val = is_string($v) ? trim($v) : $v;
            if ($val === '' || $val === null) {
                continue;
            }
            if ($val === '********' && in_array((string) $k, $secretKeys, true)) {
                continue;
            }
            $old[$k] = $val;
        }
        return self::cleanConfig($old);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function cleanConfig(array $config): array
    {
        $clean = [];
        foreach ($config as $k => $v) {
            $key = trim((string) $k);
            if ($key === '') {
                continue;
            }
            if (is_string($v)) {
                $v = trim($v);
                if ($v === '') {
                    continue;
                }
            } elseif ($v === null) {
                continue;
            }
            $clean[$key] = $v;
        }
        return $clean;
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): array
    {
        $cfg = $row['config'] ?? '{}';
        if (is_string($cfg)) {
            $decoded = json_decode($cfg, true);
            $row['config'] = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($cfg)) {
            $row['config'] = [];
        }
        $row['enabled'] = (int) ($row['enabled'] ?? 0) === 1;
        $row['test_mode'] = (int) ($row['test_mode'] ?? 0) === 1;
        $row['min_amount_rub'] = (float) ($row['min_amount_rub'] ?? 0);
        return $row;
    }
}
