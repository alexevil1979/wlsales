<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\PaymentService;

final class CheckoutController
{
    public function checkout(string $productId): void
    {
        Csrf::requireValid();
        Auth::requireLogin();

        $product = Product::findById((int) $productId);
        if (!$product || !in_array($product['status'], ['available', 'preorder'], true)) {
            flash('error', 'Лот недоступен для заказа.');
            redirect('/catalog');
        }

        $tariff = (string) ($_POST['tariff'] ?? '');
        if (!in_array($tariff, ['rent', 'inst2', 'inst4', 'forever'], true)) {
            flash('error', 'Выберите тариф.');
            redirect('/catalog/' . $product['slug']);
        }

        $amount = Product::priceForTariff($product, $tariff);
        if ($amount <= 0) {
            flash('error', 'Тариф временно недоступен.');
            redirect('/catalog/' . $product['slug']);
        }

        $status = $product['status'] === 'preorder' ? 'awaiting_payment' : 'awaiting_payment';
        $orderId = Order::create((int) Auth::id(), (int) $product['id'], $tariff, $amount, $status);

        if ($product['status'] === 'available') {
            Product::setStatus((int) $product['id'], 'reserved');
        }

        flash('success', 'Заказ создан. Выберите способ оплаты.');
        redirect('/pay/' . $orderId);
    }

    public function pay(string $orderId): void
    {
        Auth::requireLogin();
        $order = Order::findById((int) $orderId);
        if (!$order || (int) $order['user_id'] !== (int) Auth::id()) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Заказ не найден'], 'account');
            return;
        }

        $provider = isset($_GET['provider']) ? (string) $_GET['provider'] : '';
        $gateways = PaymentService::enabled((float) $order['amount']);
        if ($provider !== '') {
            $gw = PaymentService::byName($provider);
            if ($gw && $gw->isEnabled() && $order['status'] === 'awaiting_payment') {
                // Уже создан платёж — показываем реквизиты
            }
        }

        View::render('checkout/pay', [
            'title' => 'Оплата заказа #' . $order['id'],
            'order' => $order,
            'gateways' => $gateways,
            'provider' => $provider,
            'payments' => Payment::forOrder((int) $order['id']),
        ], 'account');
    }

    public function startPayment(string $orderId): void
    {
        Csrf::requireValid();
        Auth::requireLogin();
        $order = Order::findById((int) $orderId);
        if (!$order || (int) $order['user_id'] !== (int) Auth::id()) {
            redirect('/account/orders');
        }
        if ($order['status'] !== 'awaiting_payment') {
            flash('error', 'Заказ уже обработан.');
            redirect('/account/orders/' . $orderId);
        }

        $provider = (string) ($_POST['provider'] ?? '');
        $gw = PaymentService::byName($provider);
        if (!$gw || !$gw->isEnabled()) {
            flash('error', 'Способ оплаты недоступен.');
            redirect('/pay/' . $orderId);
        }

        try {
            $result = $gw->createPayment($order);
            Order::setProvider((int) $order['id'], $provider);
            if (str_starts_with($result['url'], 'http')) {
                redirect($result['url']);
            }
            redirect($result['url']);
        } catch (\Throwable $e) {
            flash('error', 'Не удалось создать платёж. Попробуйте другой способ.');
            redirect('/pay/' . $orderId);
        }
    }

    public function markPaid(string $orderId): void
    {
        Csrf::requireValid();
        Auth::requireLogin();
        $order = Order::findById((int) $orderId);
        if (!$order || (int) $order['user_id'] !== (int) Auth::id()) {
            redirect('/account/orders');
        }

        $payments = Payment::forOrder((int) $order['id']);
        $payment = $payments[0] ?? null;
        if (!$payment) {
            flash('error', 'Сначала выберите способ оплаты.');
            redirect('/pay/' . $orderId);
        }
        if (in_array($payment['status'], ['waiting_confirm', 'succeeded'], true)) {
            flash('success', 'Ожидаем подтверждения администратора.');
            redirect('/account/orders/' . $orderId);
        }

        Payment::updateStatus((int) $payment['id'], 'waiting_confirm');
        flash('success', 'Отметили оплату. Администратор проверит поступление и выдаст доступы.');
        redirect('/account/orders/' . $orderId);
    }
}
