<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;

final class YooKassaGateway implements PaymentGatewayInterface
{
    /** @param array<string, mixed>|null $gatewayRow */
    public function __construct(private readonly ?array $gatewayRow = null)
    {
    }

    public function name(): string
    {
        return 'yookassa';
    }

    public function isEnabled(): bool
    {
        $row = $this->row();
        if ($row && !(bool) $row['enabled']) {
            return false;
        }
        return $this->shopId() !== '' && $this->secret() !== '';
    }

    public function createPayment(array $order): array
    {
        $row = $this->row() ?? [];
        $shopId = $this->shopId();
        $secret = $this->secret();
        $returnUrl = $this->cfg('return_url', pay_cfg('pay_yookassa_return_url', 'YOOKASSA_RETURN_URL', app_url('/account/orders')));
        $amount = PaymentService::chargeAmount($row, (float) $order['amount']);
        $idempotenceKey = bin2hex(random_bytes(16));

        $payload = [
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $returnUrl,
            ],
            'capture' => true,
            'description' => 'Заказ #' . $order['id'] . ' WL Sales',
            'metadata' => [
                'order_id' => (int) $order['id'],
            ],
            'test' => $row ? (bool) ($row['test_mode'] ?? false) : false,
        ];

        $ch = curl_init('https://api.yookassa.ru/v3/payments');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => $shopId . ':' . $secret,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Idempotence-Key: ' . $idempotenceKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $response, true) ?: [];
        if ($code >= 400 || empty($data['id'])) {
            throw new \RuntimeException('YooKassa: не удалось создать платёж');
        }

        $invoiceId = (string) $data['id'];
        Payment::create(
            (int) $order['id'],
            $this->name(),
            (float) $order['amount'],
            $invoiceId,
            'pending',
            (string) $response
        );

        $url = $data['confirmation']['confirmation_url'] ?? ('/pay/' . $order['id']);
        return ['url' => $url, 'invoiceId' => $invoiceId];
    }

    public function handleWebhook(array $payload): void
    {
        $event = $payload['event'] ?? '';
        $object = $payload['object'] ?? [];
        if ($event !== 'payment.succeeded' || empty($object['id'])) {
            return;
        }

        $externalId = (string) $object['id'];
        $existing = Payment::findByExternal($this->name(), $externalId);
        if (!$existing) {
            return;
        }
        if ($existing['status'] === 'succeeded') {
            return;
        }

        $paidAmount = (float) ($object['amount']['value'] ?? 0);
        $order = Order::findById((int) $existing['order_id']);
        if (!$order) {
            return;
        }
        $expected = PaymentService::chargeAmount($this->row() ?? [], (float) $order['amount']);
        if (abs($paidAmount - $expected) > 0.01 && abs($paidAmount - (float) $order['amount']) > 0.01) {
            return;
        }

        Payment::updateStatus((int) $existing['id'], 'succeeded', json_encode($payload, JSON_UNESCAPED_UNICODE));
        OrderService::markPaid((int) $order['id'], $this->name());
    }

    private function shopId(): string
    {
        $v = $this->cfg('shop_id');
        return $v !== '' ? $v : pay_cfg('pay_yookassa_shop_id', 'YOOKASSA_SHOP_ID');
    }

    private function secret(): string
    {
        $v = $this->cfg('secret_key');
        return $v !== '' ? $v : pay_cfg('pay_yookassa_secret_key', 'YOOKASSA_SECRET_KEY');
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
        return $this->gatewayRow ?? PaymentGateway::findByCode('yookassa');
    }
}
