<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ProxySubscription
{
    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT s.*, p.title AS plan_title, p.domains_limit, p.dedicated_ip, p.price_month,
                    n.public_ip AS node_ip, n.location AS node_location, u.email AS user_email
             FROM proxy_subscriptions s
             JOIN proxy_plans p ON p.id = s.plan_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN proxy_nodes n ON n.id = s.node_id
             WHERE s.id = ? LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, int $planId, string $status = 'awaiting_payment'): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO proxy_subscriptions (user_id, plan_id, node_id, status, period_end, created_at)
             VALUES (?,?,NULL,?,NULL,?)'
        );
        $st->execute([$userId, $planId, $status, now_dt()]);
        return (int) Database::pdo()->lastInsertId();
    }

    /** @return list<array> */
    public static function forUser(int $userId): array
    {
        $st = Database::pdo()->prepare(
            'SELECT s.*, p.title AS plan_title, p.domains_limit, p.dedicated_ip,
                    n.public_ip AS node_ip
             FROM proxy_subscriptions s
             JOIN proxy_plans p ON p.id = s.plan_id
             LEFT JOIN proxy_nodes n ON n.id = s.node_id
             WHERE s.user_id = ? ORDER BY s.id DESC'
        );
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    /** @return list<array> */
    public static function allAdmin(?string $status = null): array
    {
        $sql = 'SELECT s.*, p.title AS plan_title, p.domains_limit, p.dedicated_ip,
                       n.public_ip AS node_ip, u.email AS user_email
                FROM proxy_subscriptions s
                JOIN proxy_plans p ON p.id = s.plan_id
                JOIN users u ON u.id = s.user_id
                LEFT JOIN proxy_nodes n ON n.id = s.node_id';
        $params = [];
        if ($status) {
            $sql .= ' WHERE s.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY s.id DESC';
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        $st = Database::pdo()->prepare('UPDATE proxy_subscriptions SET status = ?, updated_at = ? WHERE id = ?');
        $st->execute([$status, now_dt(), $id]);
    }

    public static function assignNode(int $id, int $nodeId): void
    {
        $st = Database::pdo()->prepare('UPDATE proxy_subscriptions SET node_id = ?, updated_at = ? WHERE id = ?');
        $st->execute([$nodeId, now_dt(), $id]);
    }

    public static function activate(int $id, int $months = 1): void
    {
        $sub = self::findById($id);
        $from = $sub && $sub['period_end'] && $sub['period_end'] > date('Y-m-d')
            ? $sub['period_end']
            : date('Y-m-d');
        $end = date('Y-m-d', strtotime($from . ' +' . $months . ' month'));
        $st = Database::pdo()->prepare(
            "UPDATE proxy_subscriptions SET status = 'active', period_end = ?, updated_at = ? WHERE id = ?"
        );
        $st->execute([$end, now_dt(), $id]);
    }

    /** @return list<array> */
    public static function expiringInDays(int $days): array
    {
        $st = Database::pdo()->prepare(
            "SELECT s.*, u.email, u.name, p.title AS plan_title
             FROM proxy_subscriptions s
             JOIN users u ON u.id = s.user_id
             JOIN proxy_plans p ON p.id = s.plan_id
             WHERE s.status = 'active' AND s.period_end = DATE_ADD(CURDATE(), INTERVAL ? DAY)"
        );
        $st->bindValue(1, $days, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** @return list<array> */
    public static function dueToday(): array
    {
        return Database::pdo()->query(
            "SELECT s.*, u.email, u.name, p.title AS plan_title
             FROM proxy_subscriptions s
             JOIN users u ON u.id = s.user_id
             JOIN proxy_plans p ON p.id = s.plan_id
             WHERE s.status = 'active' AND s.period_end <= CURDATE()"
        )->fetchAll();
    }

    public static function domainCount(int $subId): int
    {
        $st = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM proxy_domains WHERE subscription_id = ? AND status <> 'disabled'"
        );
        $st->execute([$subId]);
        return (int) $st->fetchColumn();
    }

    /** @return list<array> */
    public static function forNode(int $nodeId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM proxy_subscriptions WHERE node_id = ?');
        $st->execute([$nodeId]);
        return $st->fetchAll();
    }
}
