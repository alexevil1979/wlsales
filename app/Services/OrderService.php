<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Mailer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Server;
use App\Models\User;

final class OrderService
{
    public static function markPaid(int $orderId, string $provider = ''): void
    {
        $order = Order::findById($orderId);
        if (!$order) {
            return;
        }
        if (in_array($order['status'], ['paid', 'delivered', 'pending_stock'], true)) {
            return;
        }

        $product = Product::findById((int) $order['product_id']);
        $newStatus = 'paid';
        if ($product && ($product['status'] ?? '') === 'preorder') {
            $newStatus = 'pending_stock';
            Product::setStatus((int) $product['id'], 'reserved');
        } elseif ($product && in_array($product['status'], ['available', 'reserved'], true)) {
            Product::setStatus((int) $product['id'], 'reserved');
        }

        if ($newStatus === 'pending_stock') {
            Order::setStatus($orderId, 'pending_stock');
            $st = Database::pdo()->prepare('UPDATE orders SET paid_at = ?, updated_at = ? WHERE id = ? AND paid_at IS NULL');
            $st->execute([now_dt(), now_dt(), $orderId]);
        } else {
            Order::setStatus($orderId, 'paid');
        }
        if ($provider !== '') {
            Order::setProvider($orderId, $provider);
        }

        $user = User::findById((int) $order['user_id']);
        if ($user) {
            $html = '<p>Заказ #' . (int) $orderId . ' оплачен.</p>'
                . '<p>Ожидайте выдачи доступов в кабинете: ' . e(app_url('/account/orders/' . $orderId)) . '</p>';
            Mailer::send($user['email'], 'Оплата получена — заказ #' . $orderId, $html);
        }
    }

    public static function deliver(int $orderId, int $serverId): void
    {
        $order = Order::findById($orderId);
        if (!$order) {
            throw new \RuntimeException('Заказ не найден');
        }
        $server = Server::findById($serverId);
        if (!$server) {
            throw new \RuntimeException('Сервер не найден');
        }

        Server::assign($serverId, (int) $order['user_id'], $orderId);
        Order::setStatus($orderId, 'delivered');
        Product::setStatus((int) $order['product_id'], 'sold');

        $user = User::findById((int) $order['user_id']);
        if ($user) {
            Mailer::send(
                $user['email'],
                'Доступы выданы — заказ #' . $orderId,
                '<p>Сервер по заказу #' . $orderId . ' выдан. Смотрите кабинет: '
                . e(app_url('/account/servers')) . '</p>'
            );
        }
    }

    public static function confirmManualPayment(int $paymentId): void
    {
        $payment = Payment::findById($paymentId);
        if (!$payment) {
            throw new \RuntimeException('Платёж не найден');
        }
        if ($payment['status'] === 'succeeded') {
            return;
        }
        Payment::updateStatus($paymentId, 'succeeded');
        self::markPaid((int) $payment['order_id'], (string) $payment['provider']);
    }
}
