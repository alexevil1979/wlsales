<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Models\User;
use Throwable;

/**
 * CLI: php public/index.php reset-admin
 */
final class ResetAdminController
{
    public function run(): void
    {
        if (PHP_SAPI !== 'cli') {
            fwrite(STDERR, "CLI only\n");
            exit(1);
        }

        try {
            $email = trim((string) Env::get('ADMIN_EMAIL', 'admin@white-list.space'));
            $password = (string) Env::get('ADMIN_PASSWORD', 'ChangeMeAdmin2026!');
            if ($email === '' || $password === '') {
                fwrite(STDERR, "ERROR: ADMIN_EMAIL / ADMIN_PASSWORD empty in .env\n");
                exit(1);
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $existing = User::findByEmail($email);

            if ($existing) {
                $st = Database::pdo()->prepare(
                    "UPDATE users SET password_hash = ?, role = 'admin', is_banned = 0 WHERE id = ?"
                );
                $st->execute([$hash, (int) $existing['id']]);
                echo "OK: password reset\n";
                echo "email: {$email}\n";
                echo "id: {$existing['id']}\n";
                echo "password: (from ADMIN_PASSWORD in .env)\n";
                return;
            }

            $st = Database::pdo()->prepare(
                "INSERT INTO users (email, password_hash, name, telegram, role, is_banned, created_at)
                 VALUES (?, ?, 'РђРґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂ', '', 'admin', 0, ?)"
            );
            $st->execute([$email, $hash, now_dt()]);
            echo "OK: admin created\n";
            echo "email: {$email}\n";
            echo "id: " . Database::pdo()->lastInsertId() . "\n";
            echo "password: (from ADMIN_PASSWORD in .env)\n";
        } catch (Throwable $e) {
            fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
            exit(1);
        }
    }
}
