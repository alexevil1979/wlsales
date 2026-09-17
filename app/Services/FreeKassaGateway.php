<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Models\Order;
use App\Models\Payment;

/**
 * FreeKassa — как в fullvpnservice (СБП / карты РФ / world card).
 */
final class FreeKassaGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly string $code = 'freekassa_sbp')
    {
    }

    public function name(): string
    {
        return $this->code;
    }

    public function isEnabled(): bool
    {
        $shop = trim((string) Env::get('FREEKASSA_SHOP_ID', ''));
        $key = trim((string) Env::get('FREEKASSA_API_KEY', ''));
        return $shop !== '' && $key !== '';
    }

    public function createPayment(array $order): array
    {
        $shopId = (int) Env::get('FREEKASSA_SHOP_ID', '0');
        $apiKey = (string) Env::get('FREEKASSA_API_KEY', '');
        $base = rtrim((string) Env::get('FREEKASSA_API_BASE', 'https://api.fk.life/v1'), '/');
        $method = $this->paymentMethod();

        $amount = round((float) $order['amount'], 2);
        $currency = 'RUB';
        if ($this->code === 'freekassa_world_card') {
            $rate = (float) setting('usd_rate', Env::get('USD_RATE', '90') ?? '90');
            if ($rate <= 0) {
                $rate = 90;
            }
            $amount = max(1.0, round($amount / $rate, 2));
            $currency = 'USD';
        }

        $paymentId = Payment::create(
            (int) $order['id'],
            $this->code,
            (float) $order['amount'],
            '',
            'pending'
        );

        $payload = [
            'shopId' => $shopId,
            'nonce' => (int) (microtime(true) * 1000),
            'paymentId' => (string) $paymentId,
            'i' => $method,
            'email' => (string) ($order['user_email'] ?? 'client@wlsales.local'),
            'ip' => client_ip(),
            'amount' => (float) number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'notification_url' => app_url('/webhooks/freekassa'),
            'success_url' => app_url('/account/orders/' . $order['id']),
            'failure_url' => app_url('/pay/' . $order['id']),
        ];
        $payload['signature'] = $this->apiSignature($payload, $apiKey);

        $ch = curl_init($base . '/orders/create');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);
        $raw = (string) curl_exec($ch);
        curl_close($ch);
        $data = json_decode($raw, true) ?: [];

        $url = (string) ($data['location'] ?? $data['orderId']['location'] ?? $data['data']['location'] ?? '');
        if ($url === '') {
            foreach (['url', 'payment_url', 'checkout_url'] as $k) {
                if (!empty($data[$k]) && is_string($data[$k])) {
                    $url = $data[$k];
                    break;
                }
            }
        }
        $external = (string) ($data['orderId'] ?? $data['order_id'] ?? $data['data']['orderId'] ?? $paymentId);
        Payment::updateStatus($paymentId, 'pending', $raw);
        // store external
        $st = \App\Core\Database::pdo()->prepare('UPDATE payments SET external_id = ? WHERE id = ?');
        $st->execute([$external, $paymentId]);

        if ($url === '') {
            throw new \RuntimeException('FreeKassa: не получен URL оплаты');
        }

        return ['url' => $url, 'invoiceId' => (string) $external];
    }

    public function handleWebhook(array $payload): void
    {
        $secret2 = trim((string) Env::get('FREEKASSA_SECRET_WORD_2', ''));
        $merchantId = trim((string) ($payload['MERCHANT_ID'] ?? ''));
        $amount = trim((string) ($payload['AMOUNT'] ?? ''));
        $orderId = trim((string) ($payload['MERCHANT_ORDER_ID'] ?? ''));
        $sign = strtolower(trim((string) ($payload['SIGN'] ?? '')));
        $shop = trim((string) Env::get('FREEKASSA_SHOP_ID', ''));

        if ($secret2 !== '') {
            $calc = md5($merchantId . ':' . $amount . ':' . $secret2 . ':' . $orderId);
            if (!hash_equals(strtolower($calc), $sign)) {
                return;
            }
        }
        if ($shop !== '' && $merchantId !== '' && $merchantId !== $shop) {
            return;
        }

        $payment = is_numeric($orderId) ? Payment::findById((int) $orderId) : null;
        if (!$payment || $payment['status'] === 'succeeded') {
            return;
        }
        Payment::updateStatus((int) $payment['id'], 'succeeded', json_encode($payload, JSON_UNESCAPED_UNICODE));
        OrderService::markPaid((int) $payment['order_id'], $this->code);
    }

    private function paymentMethod(): int
    {
        return match ($this->code) {
            'freekassa_card' => 36,
            'freekassa_world_card' => 32,
            default => 44, // SBP
        };
    }

    /** @param array<string, mixed> $data */
    private function apiSignature(array $data, string $apiKey): string
    {
        ksort($data);
        $parts = [];
        foreach ($data as $k => $v) {
            if ($k === 'signature') {
                continue;
            }
            if (is_bool($v)) {
                $parts[] = $v ? 'true' : 'false';
            } elseif (is_array($v)) {
                continue;
            } else {
                $parts[] = (string) $v;
            }
        }
        return hash_hmac('sha256', implode('|', $parts), $apiKey);
    }
}
