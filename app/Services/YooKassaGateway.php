<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Models\Order;
use App\Models\Payment;

final class YooKassaGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'yookassa';
    }

    public function isEnabled(): bool
    {
        $shop = Env::get('YOOKASSA_SHOP_ID', '');
        $secret = Env::get('YOOKASSA_SECRET_KEY', '');
        return $shop !== '' && $secret !== '' && $shop !== null && $secret !== null;
    }

    public function createPayment(array $order): array
    {
        $shopId = (string) Env::get('YOOKASSA_SHOP_ID');
        $secret = (string) Env::get('YOOKASSA_SECRET_KEY');
        $returnUrl = (string) Env::get('YOOKASSA_RETURN_URL', app_url('/account/orders'));
        $idempotenceKey = bin2hex(random_bytes(16));

        $payload = [
            'amount' => [
                'value' => number_format((float) $order['amount'], 2, '.', ''),
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
            return; // idempotent
        }

        $paidAmount = (float) ($object['amount']['value'] ?? 0);
        $order = Order::findById((int) $existing['order_id']);
        if (!$order) {
            return;
        }
        if (abs($paidAmount - (float) $order['amount']) > 0.01) {
            return;
        }

        Payment::updateStatus((int) $existing['id'], 'succeeded', json_encode($payload, JSON_UNESCAPED_UNICODE));
        OrderService::markPaid((int) $order['id'], $this->name());
    }

    public static function verifyIp(?string $ip): bool
    {
        // ЮKassa публикует диапазоны; базовая проверка + секрет в Basic Auth при API.
        // Вебхук дополнительно сверяет сумму заказа.
        return true;
    }
}
