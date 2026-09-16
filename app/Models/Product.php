<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Product
{
    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM products WHERE slug = ? LIMIT 1');
        $st->execute([$slug]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return list<array> */
    public static function catalog(?string $vendor = null, ?string $location = null, ?string $status = null, int $limit = 100): array
    {
        $sql = "SELECT * FROM products WHERE status IN ('available','preorder','reserved')";
        $params = [];
        if ($vendor) {
            $sql .= ' AND vendor = ?';
            $params[] = $vendor;
        }
        if ($location) {
            $sql .= ' AND location = ?';
            $params[] = $location;
        }
        if ($status) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY sort ASC, id ASC LIMIT ' . (int) $limit;
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @return list<array> */
    public static function featured(int $limit = 6): array
    {
        $st = Database::pdo()->prepare(
            "SELECT * FROM products WHERE status IN ('available','preorder') ORDER BY sort ASC, id ASC LIMIT ?"
        );
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** @return list<array> */
    public static function allAdmin(): array
    {
        return Database::pdo()->query('SELECT * FROM products ORDER BY sort ASC, id DESC')->fetchAll();
    }

    /** @return list<string> */
    public static function vendors(): array
    {
        return Database::pdo()->query(
            "SELECT DISTINCT vendor FROM products WHERE status IN ('available','preorder','reserved') ORDER BY vendor"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return list<string> */
    public static function locations(): array
    {
        return Database::pdo()->query(
            "SELECT DISTINCT location FROM products WHERE status IN ('available','preorder','reserved') ORDER BY location"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function create(array $data): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO products (slug, title, vendor, location, subnet, cpu, ram_gb, disk_gb, nic, traffic, description,
             price_rent, price_forever, price_inst_2, price_inst_4, status, sort, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $data['slug'], $data['title'], $data['vendor'], $data['location'], $data['subnet'],
            $data['cpu'], (int) $data['ram_gb'], (int) $data['disk_gb'], $data['nic'], $data['traffic'],
            $data['description'], $data['price_rent'], $data['price_forever'], $data['price_inst_2'],
            $data['price_inst_4'], $data['status'], (int) $data['sort'], now_dt(),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $st = Database::pdo()->prepare(
            'UPDATE products SET slug=?, title=?, vendor=?, location=?, subnet=?, cpu=?, ram_gb=?, disk_gb=?, nic=?, traffic=?,
             description=?, price_rent=?, price_forever=?, price_inst_2=?, price_inst_4=?, status=?, sort=?, updated_at=?
             WHERE id=?'
        );
        $st->execute([
            $data['slug'], $data['title'], $data['vendor'], $data['location'], $data['subnet'],
            $data['cpu'], (int) $data['ram_gb'], (int) $data['disk_gb'], $data['nic'], $data['traffic'],
            $data['description'], $data['price_rent'], $data['price_forever'], $data['price_inst_2'],
            $data['price_inst_4'], $data['status'], (int) $data['sort'], now_dt(), $id,
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $st = Database::pdo()->prepare('UPDATE products SET status = ?, updated_at = ? WHERE id = ?');
        $st->execute([$status, now_dt(), $id]);
    }

    public static function delete(int $id): void
    {
        $st = Database::pdo()->prepare('DELETE FROM products WHERE id = ?');
        $st->execute([$id]);
    }

    public static function priceForTariff(array $product, string $tariff): float
    {
        return match ($tariff) {
            'rent' => (float) $product['price_rent'],
            'inst2' => (float) $product['price_inst_2'],
            'inst4' => (float) $product['price_inst_4'],
            'forever' => (float) $product['price_forever'],
            default => 0.0,
        };
    }
}
