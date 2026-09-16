<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;

final class ManualSbpGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'manual_sbp';
    }

    public function isEnabled(): bool
    {
        return true; // всегда доступен; реквизиты из settings
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
        // Ручное подтверждение админом — вебхук не используется
    }

    public function markWaitingConfirm(int $paymentId): void
    {
        Payment::updateStatus($paymentId, 'waiting_confirm');
    }
}
