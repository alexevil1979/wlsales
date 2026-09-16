<?php

declare(strict_types=1);

namespace App\Services;

final class PaymentService
{
    /** @return list<PaymentGatewayInterface> */
    public static function gateways(): array
    {
        return [
            new ManualSbpGateway(),
            new YooKassaGateway(),
            new CryptoStubGateway(),
        ];
    }

    /** @return list<PaymentGatewayInterface> */
    public static function enabled(): array
    {
        return array_values(array_filter(self::gateways(), static fn ($g) => $g->isEnabled()));
    }

    public static function byName(string $name): ?PaymentGatewayInterface
    {
        foreach (self::gateways() as $g) {
            if ($g->name() === $name) {
                return $g;
            }
        }
        return null;
    }
}
