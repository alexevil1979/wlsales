<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;

final class ManualSbpGateway implements PaymentGatewayInterface
{
    /** @param array<string, mixed>|null $gatewayRow */
    public function __construct(private readonly ?array $gatewayRow = null)
    {
    }

    public function name(): string
    {
        return 'manual_sbp';
    }

    public function isEnabled(): bool
    {
        $row = $this->row();
        if ($row && !(bool) $row['enabled']) {
            return false;
        }
        return true;
    }

    public function createPayment(array $order): array
    {
        $invoiceId = 'sbp-' . $order['id'] . '-' . bin2hex(random_bytes(4));
        Payment::create(
            (int) $order['id'],
            $this->name(),
            (float) $order['amount'],
            $invoiceId,
            'pending'
        );
        return [
            'url' => '/pay/' . $order['id'] . '?provider=manual_sbp',
            'invoiceId' => $invoiceId,
        ];
    }

    public function handleWebhook(array $payload): void
    {
    }

    public function markWaitingConfirm(int $paymentId): void
    {
        Payment::updateStatus($paymentId, 'waiting_confirm');
    }

    public function phone(): string
    {
        $row = $this->row();
        $fromGw = $row ? PaymentGateway::cfgString($row, 'phone') : '';
        return $fromGw !== '' ? $fromGw : setting('sbp_phone', '');
    }

    public function comment(): string
    {
        $row = $this->row();
        $fromGw = $row ? PaymentGateway::cfgString($row, 'comment') : '';
        return $fromGw !== '' ? $fromGw : setting('sbp_comment', 'Оплата заказа #{order_id}');
    }

    /** @return array<string, mixed>|null */
    private function row(): ?array
    {
        return $this->gatewayRow ?? PaymentGateway::findByCode('manual_sbp');
    }
}
