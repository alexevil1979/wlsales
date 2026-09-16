<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Product;

final class CatalogController
{
    public function index(): void
    {
        $vendor = isset($_GET['vendor']) ? trim((string) $_GET['vendor']) : null;
        $location = isset($_GET['location']) ? trim((string) $_GET['location']) : null;
        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : null;
        if ($vendor === '') {
            $vendor = null;
        }
        if ($location === '') {
            $location = null;
        }
        if ($status === '' || !in_array($status, ['available', 'preorder'], true)) {
            $status = null;
        }

        View::render('catalog/index', [
            'title' => 'Каталог серверов — WL Sales',
            'description' => 'Каталог VPS и серверов с публичным IP: фильтры по хостеру, городу и наличию.',
            'products' => Product::catalog($vendor, $location, $status),
            'vendors' => Product::vendors(),
            'locations' => Product::locations(),
            'filterVendor' => $vendor,
            'filterLocation' => $location,
            'filterStatus' => $status,
        ]);
    }

    public function show(string $slug): void
    {
        $product = Product::findBySlug($slug);
        if (!$product || ($product['status'] ?? '') === 'hidden') {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Лот не найден']);
            return;
        }

        View::render('catalog/show', [
            'title' => $product['title'] . ' — WL Sales',
            'description' => mb_substr(strip_tags((string) $product['description']), 0, 160),
            'product' => $product,
        ]);
    }
}
