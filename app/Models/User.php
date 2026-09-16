<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(string $email, string $password, string $name, string $telegram = ''): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO users (email, password_hash, name, telegram, role, is_banned, created_at) VALUES (?, ?, ?, ?, ?, 0, ?)'
        );
        $st->execute([
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $name,
            $telegram,
            'user',
            now_dt(),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function updatePassword(int $id, string $password): void
    {
        $st = Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $st->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function updateProfile(int $id, string $name, string $telegram): void
    {
        $st = Database::pdo()->prepare('UPDATE users SET name = ?, telegram = ? WHERE id = ?');
        $st->execute([$name, $telegram, $id]);
    }

    /** @return list<array> */
    public static function all(int $limit = 200): array
    {
        $st = Database::pdo()->prepare('SELECT id, email, name, telegram, role, is_banned, created_at FROM users ORDER BY id DESC LIMIT ?');
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function setBanned(int $id, bool $banned): void
    {
        $st = Database::pdo()->prepare('UPDATE users SET is_banned = ? WHERE id = ?');
        $st->execute([$banned ? 1 : 0, $id]);
    }

    public static function setRole(int $id, string $role): void
    {
        $st = Database::pdo()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $st->execute([$role, $id]);
    }

    public static function count(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
