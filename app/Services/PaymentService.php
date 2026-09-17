<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentGateway;
use App\Services\Payment\GatewaySeeder;

final class PaymentService
{
    /** @return array<string, class-string<PaymentGatewayInterface>> */
    public static function driverMap(): array
    {
        return [
            'manual_sbp' => ManualSbpGateway::class,
            'crypto_usdt' => CryptoStubGateway::class,
            'yookassa' => YooKassaGateway::class,
            'freekassa_sbp' => FreeKassaGateway::class,
            'freekassa_card' => FreeKassaGateway::class,
            'freekassa_world_card' => FreeKassaGateway::class,
            'platega' => PlategaGateway::class,
            'nowpayments' => NowPaymentsGateway::class,
            // Остальные коды из fullvpnservice — драйвер появится при подключении; в админке уже настраиваются.
        ];
    }

    /** @return list<PaymentGatewayInterface> */
    public static function gateways(): array
    {
        GatewaySeeder::ensureSeeded();
        $list = [];
        foreach (PaymentGateway::all(true) as $row) {
            $driver = self::makeDriver($row);
            if ($driver) {
                $list[] = $driver;
            }
        }
        return $list;
    }

    /** @return list<PaymentGatewayInterface> */
    public static function enabled(?float $amountRub = null): array
    {
        GatewaySeeder::ensureSeeded();
        $rows = $amountRub === null
            ? array_filter(PaymentGateway::all(true), static fn ($g) => $g['enabled'])
            : PaymentGateway::enabledForAmount($amountRub);

        $list = [];
        foreach ($rows as $row) {
            $driver = self::makeDriver($row);
            if ($driver && $driver->isEnabled()) {
                $list[] = $driver;
            }
        }
        return array_values($list);
    }

    public static function byName(string $name): ?PaymentGatewayInterface
    {
        GatewaySeeder::ensureSeeded();
        $row = PaymentGateway::findByCode($name);
        if (!$row) {
            return null;
        }
        return self::makeDriver($row);
    }

    /** @param array<string, mixed> $row */
    public static function makeDriver(array $row): ?PaymentGatewayInterface
    {
        $code = strtolower((string) ($row['code'] ?? ''));
        $map = self::driverMap();
        if (!isset($map[$code])) {
            return null;
        }
        $class = $map[$code];
        if (str_starts_with($code, 'freekassa')) {
            return new FreeKassaGateway($code, $row);
        }
        return new $class($row);
    }

    public static function label(string $code): string
    {
        $row = PaymentGateway::findByCode($code);
        if ($row) {
            return (string) $row['name'];
        }
        return match ($code) {
            'manual_sbp' => 'СБП / перевод вручную',
            'yookassa' => 'ЮKassa',
            'freekassa_sbp' => 'FreeKassa (СБП / QR)',
            'freekassa_card' => 'FreeKassa (карты РФ)',
            'freekassa_world_card' => 'FreeKassa (VISA / MasterCard USD)',
            'platega' => 'Platega',
            'nowpayments' => 'NOWPayments',
            'crypto_usdt' => 'USDT TRC20 (вручную)',
            'proxy' => 'Белый вход',
            default => $code,
        };
    }

    public static function chargeAmount(array $gatewayRow, float $orderAmount): float
    {
        $pct = (float) PaymentGateway::cfg($gatewayRow, 'extra_commission_percent', 0);
        if ($pct <= 0) {
            return round($orderAmount, 2);
        }
        return round($orderAmount * (1 + $pct / 100), 2);
    }
}
