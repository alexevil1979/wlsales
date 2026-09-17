<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Models\User;

/**
 * CLI: php public/index.php reset-admin
 * Создаёт или обновляет админа из ADMIN_EMAIL / ADMIN_PASSWORD в .env
 */
final class ResetAdminController
{
    public function run(): void
    {
        if (PHP_SAPI !== 'cli') {
            http_response_code(403);
            echo "CLI only\n";
            return;
        }

        $email = (string) Env::get('ADMIN_EMAIL', 'admin@wlsales.1tlt.ru');
        $password = (string) Env::get('ADMIN_PASSWORD', 'ChangeMeAdmin2026!');
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $existing = User::findByEmail($email);
        if ($existing) {
            $st = Database::pdo()->prepare(
                "UPDATE users SET password_hash = ?, role = 'admin', is_banned = 0 WHERE id = ?"
            );
            $st->execute([$hash, (int) $existing['id']]);
            echo "OK: password reset for {$email} (id={$existing['id']})\n";
            return;
        }

        $st = Database::pdo()->prepare(
            "INSERT INTO users (email, password_hash, name, telegram, role, is_banned, created_at)
             VALUES (?, ?, 'Администратор', '', 'admin', 0, ?)"
        );
        $st->execute([$email, $hash, now_dt()]);
        echo "OK: admin created {$email} (id=" . Database::pdo()->lastInsertId() . ")\n";
    }
}
