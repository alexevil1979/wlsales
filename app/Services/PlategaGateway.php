<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;

final class PlategaGateway implements PaymentGatewayInterface
{
    /** @param array<string, mixed>|null $gatewayRow */
    public function __construct(private readonly ?array $gatewayRow = null)
    {
    }

    public function name(): string
    {
        return 'platega';
    }

    public function isEnabled(): bool
    {
        $row = $this->row();
        if ($row && !(bool) $row['enabled']) {
            return false;
        }
        return $this->cfg('merchant_id', pay_cfg('pay_platega_merchant_id', 'PLATEGA_MERCHANT_ID')) !== ''
            && $this->cfg('secret', pay_cfg('pay_platega_secret', 'PLATEGA_SECRET')) !== '';
    }

    public function createPayment(array $order): array
    {
        $row = $this->row() ?? [];
        $merchantId = $this->cfg('merchant_id', pay_cfg('pay_platega_merchant_id', 'PLATEGA_MERCHANT_ID'));
        $secret = $this->cfg('secret', pay_cfg('pay_platega_secret', 'PLATEGA_SECRET'));
        $base = rtrim($this->cfg('api_base_url', pay_cfg('pay_platega_api_base', 'PLATEGA_API_BASE', 'https://app.platega.io')), '/');
        $path = '/' . ltrim($this->cfg('api_create_path', '/v2/transaction/process'), '/');

        $paymentId = Payment::create(
            (int) $order['id'],
            $this->name(),
            (float) $order['amount'],
            '',
            'pending'
        );

        $amount = PaymentService::chargeAmount($row, (float) $order['amount']);
        $payload = [
            'command' => $this->cfg('command', 'process') ?: 'process',
            'paymentDetails' => [
                'amount' => round($amount, 2),
                'currency' => $this->cfg('currency', 'RUB') ?: 'RUB',
            ],
            'description' => 'WL Sales #' . $order['id'],
            'return' => $this->cfg('return_url', app_url('/account/orders/' . $order['id'])),
            'failedUrl' => $this->cfg('failed_url', app_url('/pay/' . $order['id'])),
            'payload' => json_encode([
                'payment_id' => $paymentId,
                'order_id' => (int) $order['id'],
            ], JSON_UNESCAPED_UNICODE),
        ];
        $method = $this->cfg('payment_method', pay_cfg('pay_platega_payment_method', 'PLATEGA_PAYMENT_METHOD'));
        if ($method !== '') {
            $payload['paymentMethod'] = ctype_digit($method) ? (int) $method : $method;
        }

        $timeout = max(5, min(60, (int) $this->cfg('api_timeout_sec', '25')));
        $ch = curl_init($base . $path);
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
            CURLOPT_TIMEOUT => $timeout,
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

    private function cfg(string $key, string $default = ''): string
    {
        $row = $this->row();
        if ($row) {
            $v = PaymentGateway::cfgString($row, $key);
            if ($v !== '') {
                return $v;
            }
        }
        return $default;
    }

    /** @return array<string, mixed>|null */
    private function row(): ?array
    {
        return $this->gatewayRow ?? PaymentGateway::findByCode('platega');
    }
}
