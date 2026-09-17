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
            new FreeKassaGateway('freekassa_sbp'),
            new FreeKassaGateway('freekassa_card'),
            new FreeKassaGateway('freekassa_world_card'),
            new PlategaGateway(),
            new NowPaymentsGateway(),
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

    public static function label(string $code): string
    {
        return match ($code) {
            'manual_sbp' => 'СБП / перевод вручную',
            'yookassa' => 'ЮKassa (карта)',
            'freekassa_sbp' => 'FreeKassa (СБП / QR)',
            'freekassa_card' => 'FreeKassa (карты РФ)',
            'freekassa_world_card' => 'FreeKassa (VISA / MC USD)',
            'platega' => 'Platega',
            'nowpayments' => 'NOWPayments (crypto)',
            'crypto_usdt' => 'USDT TRC20 (вручную)',
            'proxy' => 'Белый вход',
            default => $code,
        };
    }
}
