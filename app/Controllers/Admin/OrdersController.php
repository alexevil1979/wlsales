<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\Order;
use App\Models\Server;
use App\Services\OrderService;

final class OrdersController
{
    public function index(): void
    {
        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
        if ($status === '') {
            $status = null;
        }
        View::render('admin/orders/index', [
            'title' => 'Заказы',
            'orders' => Order::allAdmin($status),
            'filterStatus' => $status,
        ], 'admin');
    }

    public function show(string $id): void
    {
        $order = Order::findById((int) $id);
        if (!$order) {
            flash('error', 'Заказ не найден.');
            redirect('/admin/orders');
        }
        View::render('admin/orders/show', [
            'title' => 'Заказ #' . $order['id'],
            'order' => $order,
            'servers' => Server::freeForProduct((int) $order['product_id']),
            'allServers' => Server::allAdmin(),
        ], 'admin');
    }

    public function updateStatus(string $id): void
    {
        Csrf::requireValid();
        $status = (string) ($_POST['status'] ?? '');
        $allowed = ['new', 'awaiting_payment', 'paid', 'delivered', 'cancelled', 'refund', 'pending_stock'];
        if (!in_array($status, $allowed, true)) {
            flash('error', 'Неверный статус.');
            redirect('/admin/orders/' . $id);
        }
        Order::setStatus((int) $id, $status);
        $comment = trim((string) ($_POST['admin_comment'] ?? ''));
        if ($comment !== '') {
            Order::setComment((int) $id, $comment);
        }
        AdminLog::write((int) Auth::id(), 'order_status', client_ip(), "order={$id};status={$status}");
        flash('success', 'Статус обновлён.');
        redirect('/admin/orders/' . $id);
    }

    public function deliver(string $id): void
    {
        Csrf::requireValid();
        $serverId = (int) ($_POST['server_id'] ?? 0);
        try {
            OrderService::deliver((int) $id, $serverId);
            AdminLog::write((int) Auth::id(), 'order_deliver', client_ip(), "order={$id};server={$serverId}");
            flash('success', 'Доступы выданы клиенту.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/orders/' . $id);
    }
}
