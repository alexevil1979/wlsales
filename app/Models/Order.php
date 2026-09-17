<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Order
{
    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT o.*,
                    ' . self::titleSelect() . ',
                    p.slug AS product_slug, p.vendor, p.location,
                    u.email AS user_email, u.name AS user_name
             FROM orders o
             LEFT JOIN products p ON p.id = o.product_id
             JOIN users u ON u.id = o.user_id
             WHERE o.id = ? LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(
        int $userId,
        ?int $productId,
        string $tariff,
        float $amount,
        string $status = 'awaiting_payment',
        string $orderType = 'server',
        ?int $refId = null
    ): int {
        if (Database::hasColumn('orders', 'order_type')) {
            $st = Database::pdo()->prepare(
                'INSERT INTO orders (user_id, order_type, product_id, ref_id, tariff, amount, currency, status, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $st->execute([
                $userId,
                $orderType,
                $productId,
                $refId,
                $tariff,
                $amount,
                'RUB',
                $status,
                now_dt(),
            ]);
        } else {
            $st = Database::pdo()->prepare(
                'INSERT INTO orders (user_id, product_id, tariff, amount, currency, status, created_at)
                 VALUES (?,?,?,?,?,?,?)'
            );
            $st->execute([
                $userId,
                $productId,
                $tariff,
                $amount,
                'RUB',
                $status,
                now_dt(),
            ]);
        }
        return (int) Database::pdo()->lastInsertId();
    }

    /** @return list<array> */
    public static function forUser(int $userId): array
    {
        $st = Database::pdo()->prepare(
            'SELECT o.*,
                    ' . self::titleSelect() . ',
                    p.vendor, p.location
             FROM orders o
             LEFT JOIN products p ON p.id = o.product_id
             WHERE o.user_id = ? ORDER BY o.id DESC'
        );
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    /** @return list<array> */
    public static function allAdmin(?string $status = null, int $limit = 200): array
    {
        $sql = 'SELECT o.*,
                       ' . self::titleSelect() . ',
                       u.email AS user_email
                FROM orders o
                LEFT JOIN products p ON p.id = o.product_id
                JOIN users u ON u.id = o.user_id';
        $params = [];
        if ($status) {
            $sql .= ' WHERE o.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY o.id DESC LIMIT ' . (int) $limit;
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        $extra = '';
        $params = [$status, now_dt()];
        if ($status === 'paid') {
            $extra = ', paid_at = ?';
            $params[] = now_dt();
        }
        if ($status === 'delivered') {
            $extra = ', delivered_at = ?';
            $params[] = now_dt();
        }
        $params[] = $id;
        $st = Database::pdo()->prepare("UPDATE orders SET status = ?, updated_at = ?{$extra} WHERE id = ?");
        $st->execute($params);
    }

    public static function setProvider(int $id, string $provider): void
    {
        $st = Database::pdo()->prepare('UPDATE orders SET payment_provider = ?, updated_at = ? WHERE id = ?');
        $st->execute([$provider, now_dt(), $id]);
    }

    public static function setComment(int $id, string $comment): void
    {
        $st = Database::pdo()->prepare('UPDATE orders SET admin_comment = ?, updated_at = ? WHERE id = ?');
        $st->execute([$comment, now_dt(), $id]);
    }

    public static function countByStatus(string $status): int
    {
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM orders WHERE status = ?');
        $st->execute([$status]);
        return (int) $st->fetchColumn();
    }

    public static function revenueLastDays(int $days = 30): float
    {
        $st = Database::pdo()->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM orders WHERE status IN ('paid','delivered') AND paid_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $st->bindValue(1, $days, PDO::PARAM_INT);
        $st->execute();
        return (float) $st->fetchColumn();
    }

    public static function expireUnpaid(int $hours = 48): int
    {
        $pdo = Database::pdo();
        $cols = 'id, product_id';
        if (Database::hasColumn('orders', 'order_type')) {
            $cols .= ', order_type, ref_id';
        }
        $sel = $pdo->prepare(
            "SELECT {$cols} FROM orders
             WHERE status = 'awaiting_payment' AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)"
        );
        $sel->bindValue(1, $hours, PDO::PARAM_INT);
        $sel->execute();
        $rows = $sel->fetchAll();
        if (!$rows) {
            return 0;
        }

        $upd = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = ? WHERE id = ?");
        $prod = $pdo->prepare(
            "UPDATE products SET status = 'available', updated_at = ?
             WHERE id = ? AND status = 'reserved'"
        );
        foreach ($rows as $row) {
            $upd->execute([now_dt(), (int) $row['id']]);
            $type = (string) ($row['order_type'] ?? 'server');
            if ($type === 'server' && !empty($row['product_id'])) {
                $prod->execute([now_dt(), (int) $row['product_id']]);
            }
            if ($type === 'proxy' && !empty($row['ref_id']) && Database::hasTable('proxy_subscriptions')) {
                ProxySubscription::setStatus((int) $row['ref_id'], 'cancelled');
            }
        }
        return count($rows);
    }

    private static function titleSelect(): string
    {
        if (Database::hasColumn('orders', 'ref_id')) {
            return "COALESCE(p.title, CONCAT('Proxy #', o.ref_id), CONCAT('Заказ #', o.id)) AS product_title";
        }
        return "COALESCE(p.title, CONCAT('Заказ #', o.id)) AS product_title";
    }
}
