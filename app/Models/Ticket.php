<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Ticket
{
    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT t.*, u.email AS user_email, u.name AS user_name
             FROM tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ? LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, string $subject, ?int $orderId = null): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO tickets (user_id, order_id, subject, status, created_at) VALUES (?,?,?,?,?)'
        );
        $st->execute([$userId, $orderId, $subject, 'open', now_dt()]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function addMessage(int $ticketId, int $userId, string $body): void
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO ticket_messages (ticket_id, user_id, body, created_at) VALUES (?,?,?,?)'
        );
        $st->execute([$ticketId, $userId, $body, now_dt()]);
        $st2 = Database::pdo()->prepare('UPDATE tickets SET updated_at = ? WHERE id = ?');
        $st2->execute([now_dt(), $ticketId]);
    }

    /** @return list<array> */
    public static function messages(int $ticketId): array
    {
        $st = Database::pdo()->prepare(
            'SELECT m.*, u.name, u.role FROM ticket_messages m JOIN users u ON u.id = m.user_id
             WHERE m.ticket_id = ? ORDER BY m.id ASC'
        );
        $st->execute([$ticketId]);
        return $st->fetchAll();
    }

    /** @return list<array> */
    public static function forUser(int $userId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC');
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    /** @return list<array> */
    public static function allAdmin(): array
    {
        return Database::pdo()->query(
            'SELECT t.*, u.email AS user_email FROM tickets t JOIN users u ON u.id = t.user_id ORDER BY
             FIELD(t.status, \'open\',\'answered\',\'closed\'), t.id DESC'
        )->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        $st = Database::pdo()->prepare('UPDATE tickets SET status = ?, updated_at = ? WHERE id = ?');
        $st->execute([$status, now_dt(), $id]);
    }
}
