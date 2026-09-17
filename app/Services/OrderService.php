<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Mailer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProxyDomain;
use App\Models\ProxyNode;
use App\Models\ProxySubscription;
use App\Models\Server;
use App\Models\Ticket;
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

        $orderType = (string) ($order['order_type'] ?? 'server');

        if ($orderType === 'proxy') {
            Order::setStatus($orderId, 'paid');
            if ($provider !== '') {
                Order::setProvider($orderId, $provider);
            }
            $refId = (int) ($order['ref_id'] ?? 0);
            if ($refId > 0) {
                ProxySubscription::activate($refId, 1);
            }
            $user = User::findById((int) $order['user_id']);
            if ($user) {
                Mailer::send(
                    $user['email'],
                    'Оплата получена — белый вход #' . $orderId,
                    '<p>Подписка на белый вход оплачена.</p>'
                    . '<p>Добавьте домены в кабинете: ' . e(app_url('/account/proxy')) . '</p>'
                    . '<p>После назначения IP админом поставьте A-запись на выданный адрес.</p>'
                );
            }
            TelegramNotifier::notifyPayment(Order::findById($orderId) ?: $order, $provider);
            return;
        }

        $product = !empty($order['product_id']) ? Product::findById((int) $order['product_id']) : null;
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

        TelegramNotifier::notifyPayment(Order::findById($orderId) ?: $order, $provider);
    }

    public static function deliver(int $orderId, int $serverId): void
    {
        $order = Order::findById($orderId);
        if (!$order) {
            throw new \RuntimeException('Заказ не найден');
        }
        if (($order['order_type'] ?? 'server') === 'proxy') {
            throw new \RuntimeException('Это заказ белого входа — выдайте ноду в /admin/proxy');
        }
        $server = Server::findById($serverId);
        if (!$server) {
            throw new \RuntimeException('Сервер не найден');
        }

        // Предупреждение: сервер уже нода с чужими доменами
        $node = ProxyNode::findByServerId($serverId);
        if ($node && (int) $node['domains_used'] > 0) {
            throw new \RuntimeException(
                'Сервер уже используется как proxy-нода с доменами (' . (int) $node['domains_used'] . '). '
                . 'Нельзя выдать как dedicated VPS без очистки.'
            );
        }

        Server::assign($serverId, (int) $order['user_id'], $orderId);
        Order::setStatus($orderId, 'delivered');
        if (!empty($order['product_id'])) {
            Product::setStatus((int) $order['product_id'], 'sold');
        }

        $user = User::findById((int) $order['user_id']);
        if ($user) {
            Mailer::send(
                $user['email'],
                'Доступы выданы — заказ #' . $orderId,
                '<p>Сервер по заказу #' . $orderId . ' выдан. Смотрите кабинет: '
                . e(app_url('/account/servers')) . '</p>'
            );
        }

        TelegramNotifier::notifyDelivered(Order::findById($orderId) ?: $order, (string) ($server['ip'] ?? ''));
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
