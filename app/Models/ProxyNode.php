<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ProxyNode
{
    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM proxy_nodes WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return list<array> */
    public static function all(): array
    {
        return Database::pdo()->query(
            'SELECT n.*, s.hostname AS server_hostname, s.ip AS server_ip, s.status AS server_status
             FROM proxy_nodes n
             LEFT JOIN servers s ON s.id = n.server_id
             ORDER BY n.id DESC'
        )->fetchAll();
    }

    /** @return list<array> */
    public static function available(bool $dedicated = false): array
    {
        $sql = "SELECT * FROM proxy_nodes WHERE status = 'active' AND domains_used < domains_cap";
        if ($dedicated) {
            $sql .= ' AND domains_used = 0';
        }
        $sql .= ' ORDER BY domains_used ASC, id ASC';
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function create(array $data): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO proxy_nodes (server_id, hostname, public_ip, location, vendor, domains_used, domains_cap, status, note_admin, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $data['server_id'] ?: null,
            $data['hostname'],
            $data['public_ip'],
            $data['location'],
            $data['vendor'],
            (int) ($data['domains_used'] ?? 0),
            (int) ($data['domains_cap'] ?? 50),
            $data['status'] ?? 'active',
            $data['note_admin'] ?? null,
            now_dt(),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $st = Database::pdo()->prepare(
            'UPDATE proxy_nodes SET server_id=?, hostname=?, public_ip=?, location=?, vendor=?, domains_cap=?, status=?, note_admin=? WHERE id=?'
        );
        $st->execute([
            $data['server_id'] ?: null,
            $data['hostname'],
            $data['public_ip'],
            $data['location'],
            $data['vendor'],
            (int) $data['domains_cap'],
            $data['status'],
            $data['note_admin'] ?? null,
            $id,
        ]);
    }

    public static function recalculateUsed(int $nodeId): void
    {
        $st = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM proxy_domains d
             JOIN proxy_subscriptions s ON s.id = d.subscription_id
             WHERE s.node_id = ? AND d.status IN ('pending_dns','active','error')"
        );
        $st->execute([$nodeId]);
        $used = (int) $st->fetchColumn();
        $node = self::findById($nodeId);
        if (!$node) {
            return;
        }
        $status = $node['status'];
        if ($status !== 'dead' && $status !== 'maintenance') {
            $status = $used >= (int) $node['domains_cap'] ? 'full' : 'active';
        }
        $upd = Database::pdo()->prepare('UPDATE proxy_nodes SET domains_used = ?, status = ? WHERE id = ?');
        $upd->execute([$used, $status, $nodeId]);
    }

    public static function findByServerId(int $serverId): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM proxy_nodes WHERE server_id = ? LIMIT 1');
        $st->execute([$serverId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function markDead(int $id): void
    {
        $st = Database::pdo()->prepare("UPDATE proxy_nodes SET status = 'dead' WHERE id = ?");
        $st->execute([$id]);
    }
}
