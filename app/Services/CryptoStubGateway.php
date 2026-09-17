<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;

final class CryptoStubGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'crypto_usdt';
    }

    public function isEnabled(): bool
    {
        if (setting('pay_crypto_on', '1') === '0') {
            return false;
        }
        $addr = setting('crypto_usdt_trc20', '');
        return $addr !== '' && $addr !== 'TXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
    }

    public function createPayment(array $order): array
    {
        $invoiceId = 'usdt-' . $order['id'] . '-' . bin2hex(random_bytes(4));
        Payment::create(
            (int) $order['id'],
            $this->name(),
            (float) $order['amount'],
            $invoiceId,
            'pending'
        );
        return [
            'url' => '/pay/' . $order['id'] . '?provider=crypto_usdt',
            'invoiceId' => $invoiceId,
        ];
    }

    public function handleWebhook(array $payload): void
    {
        // Подтверждение вручную
    }
}
