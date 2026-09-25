<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Models\Payment;
use App\Models\PaymentGateway;

/**
 * FreeKassa вЂ” РєР°Рє РІ fullvpnservice (РЎР‘Рџ / РєР°СЂС‚С‹ Р Р¤ / world card).
 */
final class FreeKassaGateway implements PaymentGatewayInterface
{
    /** @param array<string, mixed>|null $gatewayRow */
    public function __construct(
        private readonly string $code = 'freekassa_sbp',
        private readonly ?array $gatewayRow = null,
    ) {
    }

    public function name(): string
    {
        return $this->code;
    }

    public function isEnabled(): bool
    {
        $row = $this->row();
        if ($row && !(bool) $row['enabled']) {
            return false;
        }
        return $this->shopId() !== '' && $this->apiKey() !== '';
    }

    public function createPayment(array $order): array
    {
        $row = $this->row() ?? [];
        $shopId = (int) $this->shopId();
        $apiKey = $this->apiKey();
        $base = rtrim($this->cfg('api_base_url', pay_cfg('pay_freekassa_api_base', 'FREEKASSA_API_BASE', 'https://api.fk.life/v1')), '/');
        $path = '/' . ltrim($this->cfg('api_create_path', '/orders/create'), '/');
        $method = $this->paymentMethod();

        $chargeBase = PaymentService::chargeAmount($row, (float) $order['amount']);
        $amount = round($chargeBase, 2);
        $currency = $this->cfg('currency', 'RUB') ?: 'RUB';
        if ($this->code === 'freekassa_world_card') {
            $rate = (float) setting('usd_rate', Env::get('USD_RATE', '90') ?? '90');
            if ($rate <= 0) {
                $rate = 90;
            }
            $amount = max(1.0, round($chargeBase / $rate, 2));
            $currency = 'USD';
        }

        $paymentId = Payment::create(
            (int) $order['id'],
            $this->code,
            (float) $order['amount'],
            '',
            'pending'
        );

        $notify = $this->cfg('notification_url', app_url('/webhooks/freekassa'));
        $success = $this->cfg('success_url', app_url('/account/orders/' . $order['id']));
        $failure = $this->cfg('failure_url', app_url('/pay/' . $order['id']));
        $ip = client_ip();
        if ($ip === '' || $ip === '0.0.0.0') {
            $ip = $this->cfg('fallback_ip', '127.0.0.1');
        }

        $payload = [
            'shopId' => $shopId,
            'nonce' => (int) (microtime(true) * 1000),
            'paymentId' => (string) $paymentId,
            'i' => $method,
            'email' => (string) ($order['user_email'] ?? 'client@white-list.space'),
            'ip' => $ip,
            'amount' => (float) number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'notification_url' => $notify,
            'success_url' => $success,
            'failure_url' => $failure,
        ];
        $payload['signature'] = $this->apiSignature($payload, $apiKey);

        $timeout = max(5, min(60, (int) $this->cfg('api_timeout_sec', '25')));
        $ch = curl_init($base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => $timeout,
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
        $st = \App\Core\Database::pdo()->prepare('UPDATE payments SET external_id = ? WHERE id = ?');
        $st->execute([$external, $paymentId]);

        if ($url === '') {
            throw new \RuntimeException('FreeKassa: РЅРµ РїРѕР»СѓС‡РµРЅ URL РѕРїР»Р°С‚С‹');
        }

        return ['url' => $url, 'invoiceId' => (string) $external];
    }

    public function handleWebhook(array $payload): void
    {
        $secret2 = $this->cfg('secret_word_2', pay_cfg('pay_freekassa_secret_word_2', 'FREEKASSA_SECRET_WORD_2'));
        $merchantId = trim((string) ($payload['MERCHANT_ID'] ?? ''));
        $amount = trim((string) ($payload['AMOUNT'] ?? ''));
        $orderId = trim((string) ($payload['MERCHANT_ORDER_ID'] ?? ''));
        $sign = strtolower(trim((string) ($payload['SIGN'] ?? '')));
        $shop = $this->shopId();

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
        OrderService::markPaid((int) $payment['order_id'], (string) $payment['provider']);
    }

    private function paymentMethod(): int
    {
        $fromConfig = $this->cfg('payment_method');
        if ($fromConfig !== '' && ctype_digit($fromConfig)) {
            return (int) $fromConfig;
        }
        return match ($this->code) {
            'freekassa_card' => 36,
            'freekassa_world_card' => 32,
            default => 44,
        };
    }

    private function shopId(): string
    {
        $v = $this->cfg('shop_id');
        return $v !== '' ? $v : pay_cfg('pay_freekassa_shop_id', 'FREEKASSA_SHOP_ID');
    }

    private function apiKey(): string
    {
        $v = $this->cfg('api_key');
        return $v !== '' ? $v : pay_cfg('pay_freekassa_api_key', 'FREEKASSA_API_KEY');
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
        return $this->gatewayRow ?? PaymentGateway::findByCode($this->code);
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
