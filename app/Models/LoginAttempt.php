<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class LoginAttempt
{
    public static function record(string $ip, string $email): void
    {
        $st = Database::pdo()->prepare('INSERT INTO login_attempts (ip, email, attempted_at) VALUES (?,?,?)');
        $st->execute([$ip, $email, now_dt()]);
    }

    public static function countRecent(string $ip, int $minutes): int
    {
        $st = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $st->bindValue(1, $ip);
        $st->bindValue(2, $minutes, PDO::PARAM_INT);
        $st->execute();
        return (int) $st->fetchColumn();
    }

    public static function purgeOlderThan(int $days): void
    {
        $st = Database::pdo()->prepare('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? DAY)');
        $st->bindValue(1, $days, PDO::PARAM_INT);
        $st->execute();
    }
}
