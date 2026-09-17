<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Models\Payment;

final class PlategaGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'platega';
    }

    public function isEnabled(): bool
    {
        return trim((string) Env::get('PLATEGA_MERCHANT_ID', '')) !== ''
            && trim((string) Env::get('PLATEGA_SECRET', '')) !== '';
    }

    public function createPayment(array $order): array
    {
        $merchantId = (string) Env::get('PLATEGA_MERCHANT_ID', '');
        $secret = (string) Env::get('PLATEGA_SECRET', '');
        $base = rtrim((string) Env::get('PLATEGA_API_BASE', 'https://app.platega.io'), '/');

        $paymentId = Payment::create(
            (int) $order['id'],
            $this->name(),
            (float) $order['amount'],
            '',
            'pending'
        );

        $payload = [
            'command' => 'process',
            'paymentDetails' => [
                'amount' => round((float) $order['amount'], 2),
                'currency' => 'RUB',
            ],
            'description' => 'WL Sales #' . $order['id'],
            'return' => app_url('/account/orders/' . $order['id']),
            'failedUrl' => app_url('/pay/' . $order['id']),
            'payload' => json_encode([
                'payment_id' => $paymentId,
                'order_id' => (int) $order['id'],
            ], JSON_UNESCAPED_UNICODE),
        ];
        $method = trim((string) Env::get('PLATEGA_PAYMENT_METHOD', ''));
        if ($method !== '') {
            $payload['paymentMethod'] = ctype_digit($method) ? (int) $method : $method;
        }

        $ch = curl_init($base . '/v2/transaction/process');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'X-MerchantId: ' . $merchantId,
                'X-Secret: ' . $secret,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);
        $raw = (string) curl_exec($ch);
        curl_close($ch);
        $data = json_decode($raw, true) ?: [];

        $url = (string) ($data['url'] ?? $data['redirect'] ?? $data['payment_url'] ?? $data['checkout_url'] ?? '');
        if ($url === '' && isset($data['data']) && is_array($data['data'])) {
            $url = (string) ($data['data']['url'] ?? $data['data']['redirect'] ?? '');
        }
        $external = (string) ($data['transactionId'] ?? $data['transaction_id'] ?? $data['id'] ?? $paymentId);
        $st = \App\Core\Database::pdo()->prepare('UPDATE payments SET external_id = ?, raw_json = ? WHERE id = ?');
        $st->execute([$external, $raw, $paymentId]);

        if ($url === '') {
            throw new \RuntimeException('Platega: не получен URL');
        }
        return ['url' => $url, 'invoiceId' => $external];
    }

    public function handleWebhook(array $payload): void
    {
        $status = strtolower((string) ($payload['status'] ?? $payload['Status'] ?? ''));
        $paid = in_array($status, ['success', 'succeeded', 'paid', 'completed', 'confirmed'], true);
        if (!$paid && !empty($payload['paid'])) {
            $paid = true;
        }
        if (!$paid) {
            return;
        }

        $paymentId = null;
        if (!empty($payload['payload'])) {
            $inner = json_decode((string) $payload['payload'], true);
            if (is_array($inner) && !empty($inner['payment_id'])) {
                $paymentId = (int) $inner['payment_id'];
            }
        }
        $payment = $paymentId ? Payment::findById($paymentId) : null;
        if (!$payment) {
            $ext = (string) ($payload['transactionId'] ?? $payload['transaction_id'] ?? $payload['id'] ?? '');
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
