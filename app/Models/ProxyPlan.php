<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ProxyPlan
{
    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM proxy_plans WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM proxy_plans WHERE slug = ? LIMIT 1');
        $st->execute([$slug]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return list<array> */
    public static function active(): array
    {
        return Database::pdo()->query(
            'SELECT * FROM proxy_plans WHERE active = 1 ORDER BY sort ASC, id ASC'
        )->fetchAll();
    }

    /** @return list<array> */
    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM proxy_plans ORDER BY sort ASC, id ASC')->fetchAll();
    }

    public static function suggestForDomains(int $count): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT * FROM proxy_plans WHERE active = 1 AND domains_limit >= ? AND dedicated_ip = 0
             ORDER BY domains_limit ASC, price_month ASC LIMIT 1'
        );
        $st->execute([$count]);
        $row = $st->fetch();
        return $row ?: null;
    }
}
