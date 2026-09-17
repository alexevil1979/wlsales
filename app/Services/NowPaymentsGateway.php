<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Models\Payment;

final class NowPaymentsGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'nowpayments';
    }

    public function isEnabled(): bool
    {
        return trim((string) Env::get('NOWPAYMENTS_API_KEY', '')) !== '';
    }

    public function createPayment(array $order): array
    {
        $key = (string) Env::get('NOWPAYMENTS_API_KEY', '');
        $payCurrency = strtolower(trim((string) Env::get('NOWPAYMENTS_PAY_CURRENCY', 'usdttrc20')));

        $paymentId = Payment::create(
            (int) $order['id'],
            $this->name(),
            (float) $order['amount'],
            '',
            'pending'
        );

        $amountRub = (float) $order['amount'];
        $rate = (float) setting('usd_rate', Env::get('USD_RATE', '90') ?? '90');
        if ($rate <= 0) {
            $rate = 90;
        }
        $amountUsd = max(0.01, round($amountRub / $rate, 2));

        $payload = [
            'price_amount' => $amountUsd,
            'price_currency' => 'usd',
            'order_id' => (string) $paymentId,
            'order_description' => 'WL Sales #' . $order['id'],
            'ipn_callback_url' => app_url('/webhooks/nowpayments'),
            'success_url' => app_url('/account/orders/' . $order['id']),
            'cancel_url' => app_url('/pay/' . $order['id']),
        ];
        if ($payCurrency !== '') {
            $payload['pay_currency'] = $payCurrency;
        }

        $ch = curl_init('https://api.nowpayments.io/v1/invoice');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $key,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);
        $raw = (string) curl_exec($ch);
        curl_close($ch);
        $data = json_decode($raw, true) ?: [];

        $url = trim((string) ($data['invoice_url'] ?? ''));
        $external = (string) ($data['id'] ?? $data['payment_id'] ?? $paymentId);
        if ($url === '' && $external !== '') {
            $url = 'https://nowpayments.io/payment/?iid=' . $external;
        }
        $st = \App\Core\Database::pdo()->prepare('UPDATE payments SET external_id = ?, raw_json = ? WHERE id = ?');
        $st->execute([$external, $raw, $paymentId]);

        if ($url === '') {
            throw new \RuntimeException('NOWPayments: не получен URL');
        }
        return ['url' => $url, 'invoiceId' => $external];
    }

    public function handleWebhook(array $payload): void
    {
        $status = strtolower((string) ($payload['payment_status'] ?? $payload['status'] ?? ''));
        if (!in_array($status, ['finished', 'confirmed'], true)) {
            return;
        }

        $orderRef = (string) ($payload['order_id'] ?? '');
        $payment = is_numeric($orderRef) ? Payment::findById((int) $orderRef) : null;
        if (!$payment) {
            $ext = (string) ($payload['invoice_id'] ?? $payload['payment_id'] ?? '');
            if ($ext !== '') {
                $payment = Payment::findByExternal($this->name(), $ext);
            }
        }
        if (!$payment || $payment['status'] === 'succeeded') {
            return;
        }
        Payment::updateStatus((int) $payment['id'], 'succeeded', json_encode($payload, JSON_UNESCAPED_UNICODE));
        OrderService::markPaid((int) $payment['order_id'], $this->name());
    }
}
