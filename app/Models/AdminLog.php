<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AdminLog
{
    public static function write(int $userId, string $action, string $ip = '', ?string $meta = null): void
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO admin_log (user_id, action, ip, meta, created_at) VALUES (?,?,?,?,?)'
        );
        $st->execute([$userId, $action, $ip, $meta, now_dt()]);
    }

    /** @return list<array> */
    public static function recent(int $limit = 50): array
    {
        $st = Database::pdo()->query(
            'SELECT l.*, u.email FROM admin_log l JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT ' . (int) $limit
        );
        return $st->fetchAll();
    }
}
