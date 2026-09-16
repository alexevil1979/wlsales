<?php

declare(strict_types=1);

namespace App\Services;

interface PaymentGatewayInterface
{
    public function name(): string;

    public function isEnabled(): bool;

    /** @return array{url: string, invoiceId: string} */
    public function createPayment(array $order): array;

    /** @param array<string, mixed> $payload */
    public function handleWebhook(array $payload): void;
}
