<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Server
{
    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM servers WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return list<array> */
    public static function allAdmin(): array
    {
        return Database::pdo()->query(
            'SELECT s.*, p.title AS product_title, u.email AS user_email
             FROM servers s
             LEFT JOIN products p ON p.id = s.product_id
             LEFT JOIN users u ON u.id = s.assigned_user_id
             ORDER BY s.id DESC'
        )->fetchAll();
    }

    /** @return list<array> */
    public static function freeForProduct(?int $productId = null): array
    {
        if ($productId) {
            $st = Database::pdo()->prepare("SELECT * FROM servers WHERE status = 'free' AND (product_id = ? OR product_id IS NULL) ORDER BY id");
            $st->execute([$productId]);
            return $st->fetchAll();
        }
        return Database::pdo()->query("SELECT * FROM servers WHERE status = 'free' ORDER BY id")->fetchAll();
    }

    /** @return list<array> */
    public static function forUser(int $userId): array
    {
        $st = Database::pdo()->prepare(
            "SELECT s.*, p.title AS product_title, p.vendor, p.location, o.tariff
             FROM servers s
             LEFT JOIN products p ON p.id = s.product_id
             LEFT JOIN orders o ON o.id = s.assigned_order_id
             WHERE s.assigned_user_id = ? AND s.status = 'assigned'
             ORDER BY s.id DESC"
        );
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public static function create(array $data): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO servers (product_id, hostname, ip, panel_url, login_hint, secret_enc, note_admin, status, created_at)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $data['product_id'] ?: null,
            $data['hostname'],
            $data['ip'],
            $data['panel_url'],
            $data['login_hint'],
            $data['secret_enc'] ?? null,
            $data['note_admin'],
            $data['status'] ?? 'free',
            now_dt(),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $st = Database::pdo()->prepare(
            'UPDATE servers SET product_id=?, hostname=?, ip=?, panel_url=?, login_hint=?, secret_enc=?, note_admin=?, status=? WHERE id=?'
        );
        $st->execute([
            $data['product_id'] ?: null,
            $data['hostname'],
            $data['ip'],
            $data['panel_url'],
            $data['login_hint'],
            $data['secret_enc'] ?? null,
            $data['note_admin'],
            $data['status'],
            $id,
        ]);
    }

    public static function assign(int $serverId, int $userId, int $orderId): void
    {
        $st = Database::pdo()->prepare(
            "UPDATE servers SET assigned_user_id = ?, assigned_order_id = ?, status = 'assigned' WHERE id = ?"
        );
        $st->execute([$userId, $orderId, $serverId]);
    }

    public static function delete(int $id): void
    {
        $st = Database::pdo()->prepare('DELETE FROM servers WHERE id = ?');
        $st->execute([$id]);
    }
}
