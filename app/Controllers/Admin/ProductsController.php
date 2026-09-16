<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\Product;

final class ProductsController
{
    public function index(): void
    {
        View::render('admin/products/index', [
            'title' => 'Товары',
            'products' => Product::allAdmin(),
        ], 'admin');
    }

    public function createForm(): void
    {
        View::render('admin/products/form', [
            'title' => 'Новый товар',
            'product' => null,
        ], 'admin');
    }

    public function create(): void
    {
        Csrf::requireValid();
        $data = $this->fromPost();
        $id = Product::create($data);
        AdminLog::write((int) Auth::id(), 'product_create', client_ip(), 'id=' . $id);
        flash('success', 'Товар создан.');
        redirect('/admin/products');
    }

    public function editForm(string $id): void
    {
        $product = Product::findById((int) $id);
        if (!$product) {
            flash('error', 'Не найдено.');
            redirect('/admin/products');
        }
        View::render('admin/products/form', [
            'title' => 'Редактирование товара',
            'product' => $product,
        ], 'admin');
    }

    public function update(string $id): void
    {
        Csrf::requireValid();
        $product = Product::findById((int) $id);
        if (!$product) {
            redirect('/admin/products');
        }
        Product::update((int) $id, $this->fromPost());
        AdminLog::write((int) Auth::id(), 'product_update', client_ip(), 'id=' . $id);
        flash('success', 'Сохранено.');
        redirect('/admin/products');
    }

    public function delete(string $id): void
    {
        Csrf::requireValid();
        Product::delete((int) $id);
        AdminLog::write((int) Auth::id(), 'product_delete', client_ip(), 'id=' . $id);
        flash('success', 'Удалено.');
        redirect('/admin/products');
    }

    /** @return array<string, mixed> */
    private function fromPost(): array
    {
        $slug = trim((string) ($_POST['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->slugify((string) ($_POST['title'] ?? 'item'));
        }
        return [
            'slug' => $slug,
            'title' => trim((string) ($_POST['title'] ?? '')),
            'vendor' => trim((string) ($_POST['vendor'] ?? '')),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'subnet' => trim((string) ($_POST['subnet'] ?? '')),
            'cpu' => trim((string) ($_POST['cpu'] ?? '')),
            'ram_gb' => (int) ($_POST['ram_gb'] ?? 0),
            'disk_gb' => (int) ($_POST['disk_gb'] ?? 0),
            'nic' => trim((string) ($_POST['nic'] ?? '')),
            'traffic' => trim((string) ($_POST['traffic'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price_rent' => (float) str_replace(',', '.', (string) ($_POST['price_rent'] ?? 0)),
            'price_forever' => (float) str_replace(',', '.', (string) ($_POST['price_forever'] ?? 0)),
            'price_inst_2' => (float) str_replace(',', '.', (string) ($_POST['price_inst_2'] ?? 0)),
            'price_inst_4' => (float) str_replace(',', '.', (string) ($_POST['price_inst_4'] ?? 0)),
            'status' => (string) ($_POST['status'] ?? 'available'),
            'sort' => (int) ($_POST['sort'] ?? 100),
        ];
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('~[^a-z0-9а-яё]+~u', '-', $text) ?? 'item';
        $text = trim($text, '-');
        return $text !== '' ? $text . '-' . substr(bin2hex(random_bytes(2)), 0, 4) : 'item';
    }
}
