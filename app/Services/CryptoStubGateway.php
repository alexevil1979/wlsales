<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;

final class CryptoStubGateway implements PaymentGatewayInterface
{
    /** @param array<string, mixed>|null $gatewayRow */
    public function __construct(private readonly ?array $gatewayRow = null)
    {
    }

    public function name(): string
    {
        return 'crypto_usdt';
    }

    public function isEnabled(): bool
    {
        $row = $this->row();
        if ($row && !(bool) $row['enabled']) {
            return false;
        }
        $addr = $this->address();
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
    }

    public function address(): string
    {
        $row = $this->row();
        $fromGw = $row ? PaymentGateway::cfgString($row, 'address') : '';
        return $fromGw !== '' ? $fromGw : setting('crypto_usdt_trc20', '');
    }

    /** @return array<string, mixed>|null */
    private function row(): ?array
    {
        return $this->gatewayRow ?? PaymentGateway::findByCode('crypto_usdt');
    }
}
