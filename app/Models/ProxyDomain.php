<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ProxyDomain
{
    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT d.*, s.user_id, s.node_id, s.status AS sub_status, n.public_ip AS node_ip
             FROM proxy_domains d
             JOIN proxy_subscriptions s ON s.id = d.subscription_id
             LEFT JOIN proxy_nodes n ON n.id = s.node_id
             WHERE d.id = ? LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return list<array> */
    public static function forSubscription(int $subId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM proxy_domains WHERE subscription_id = ? ORDER BY id DESC');
        $st->execute([$subId]);
        return $st->fetchAll();
    }

    /** @return list<array> */
    public static function forUser(int $userId): array
    {
        $st = Database::pdo()->prepare(
            'SELECT d.*, s.id AS subscription_id, n.public_ip AS node_ip, p.title AS plan_title
             FROM proxy_domains d
             JOIN proxy_subscriptions s ON s.id = d.subscription_id
             JOIN proxy_plans p ON p.id = s.plan_id
             LEFT JOIN proxy_nodes n ON n.id = s.node_id
             WHERE s.user_id = ? ORDER BY d.id DESC'
        );
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    /** @return list<array> */
    public static function queueDns(): array
    {
        return Database::pdo()->query(
            "SELECT d.*, s.user_id, u.email AS user_email, n.public_ip AS node_ip, n.id AS node_id
             FROM proxy_domains d
             JOIN proxy_subscriptions s ON s.id = d.subscription_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN proxy_nodes n ON n.id = s.node_id
             WHERE d.status = 'pending_dns'
             ORDER BY d.id ASC"
        )->fetchAll();
    }

    /** @return list<array> */
    public static function allAdmin(?string $status = null): array
    {
        $sql = "SELECT d.*, s.user_id, u.email AS user_email, n.public_ip AS node_ip, n.id AS node_id
                FROM proxy_domains d
                JOIN proxy_subscriptions s ON s.id = d.subscription_id
                JOIN users u ON u.id = s.user_id
                LEFT JOIN proxy_nodes n ON n.id = s.node_id";
        $params = [];
        if ($status) {
            $sql .= ' WHERE d.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY d.id DESC';
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public static function create(array $data): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO proxy_domains
             (subscription_id, domain, origin_host, origin_port, origin_https, origin_sni, ssl_status, status, created_at)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $data['subscription_id'],
            strtolower(trim($data['domain'])),
            trim($data['origin_host']),
            (int) ($data['origin_port'] ?? 443),
            (int) ($data['origin_https'] ?? 1),
            $data['origin_sni'] !== '' ? $data['origin_sni'] : null,
            'pending',
            'pending_dns',
            now_dt(),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateAdmin(int $id, array $data): void
    {
        $st = Database::pdo()->prepare(
            'UPDATE proxy_domains SET origin_host=?, origin_port=?, origin_https=?, origin_sni=?,
             ssl_status=?, status=?, note_admin=?, updated_at=? WHERE id=?'
        );
        $st->execute([
            $data['origin_host'],
            (int) $data['origin_port'],
            (int) $data['origin_https'],
            $data['origin_sni'] !== '' ? $data['origin_sni'] : null,
            $data['ssl_status'],
            $data['status'],
            $data['note_admin'] ?? null,
            now_dt(),
            $id,
        ]);
    }

    public static function setCheck(int $id, bool $ok, string $status): void
    {
        $st = Database::pdo()->prepare(
            'UPDATE proxy_domains SET last_check_at=?, last_check_ok=?, status=?, updated_at=? WHERE id=?'
        );
        $st->execute([now_dt(), $ok ? 1 : 0, $status, now_dt(), $id]);
    }

    public static function setSsl(int $id, string $ssl): void
    {
        $st = Database::pdo()->prepare('UPDATE proxy_domains SET ssl_status=?, updated_at=? WHERE id=?');
        $st->execute([$ssl, now_dt(), $id]);
    }

    public static function markErrorForNode(int $nodeId): int
    {
        $st = Database::pdo()->prepare(
            "UPDATE proxy_domains d
             JOIN proxy_subscriptions s ON s.id = d.subscription_id
             SET d.status = 'error', d.updated_at = ?
             WHERE s.node_id = ? AND d.status IN ('pending_dns','active')"
        );
        $st->execute([now_dt(), $nodeId]);
        return $st->rowCount();
    }

    /** @return list<string> */
    public static function resolveA(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $ips = [];
        $records = @dns_get_record($domain, DNS_A);
        if (is_array($records)) {
            foreach ($records as $r) {
                if (!empty($r['ip'])) {
                    $ips[] = $r['ip'];
                }
            }
        }
        return array_values(array_unique($ips));
    }
}
