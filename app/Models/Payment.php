<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Payment
{
    public static function create(int $orderId, string $provider, float $amount, string $externalId = '', string $status = 'pending', ?string $raw = null): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO payments (order_id, provider, external_id, amount, status, raw_json, created_at) VALUES (?,?,?,?,?,?,?)'
        );
        $st->execute([$orderId, $provider, $externalId, $amount, $status, $raw, now_dt()]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function findByExternal(string $provider, string $externalId): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM payments WHERE provider = ? AND external_id = ? LIMIT 1');
        $st->execute([$provider, $externalId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function forOrder(int $orderId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC');
        $st->execute([$orderId]);
        return $st->fetchAll();
    }

    public static function updateStatus(int $id, string $status, ?string $raw = null): void
    {
        if ($raw !== null) {
            $st = Database::pdo()->prepare('UPDATE payments SET status = ?, raw_json = ?, updated_at = ? WHERE id = ?');
            $st->execute([$status, $raw, now_dt(), $id]);
        } else {
            $st = Database::pdo()->prepare('UPDATE payments SET status = ?, updated_at = ? WHERE id = ?');
            $st->execute([$status, now_dt(), $id]);
        }
    }

    /** @return list<array> */
    public static function allAdmin(int $limit = 200): array
    {
        $st = Database::pdo()->prepare(
            'SELECT p.*, o.user_id, u.email AS user_email
             FROM payments p
             JOIN orders o ON o.id = p.order_id
             JOIN users u ON u.id = o.user_id
             ORDER BY p.id DESC LIMIT ' . (int) $limit
        );
        $st->execute();
        return $st->fetchAll();
    }

    public static function waitingConfirm(): array
    {
        return Database::pdo()->query(
            "SELECT p.*, o.amount AS order_amount, u.email
             FROM payments p
             JOIN orders o ON o.id = p.order_id
             JOIN users u ON u.id = o.user_id
             WHERE p.status = 'waiting_confirm'
             ORDER BY p.id DESC"
        )->fetchAll();
    }
}
