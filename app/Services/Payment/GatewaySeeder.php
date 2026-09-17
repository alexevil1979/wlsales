<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Core\Env;
use App\Models\PaymentGateway;

/**
 * Сид и миграция ключей из settings/.env — как HiddifySalesSeeder.
 */
final class GatewaySeeder
{
    /** @return list<array{code:string,name:string}> */
    public static function catalog(): array
    {
        return [
            ['code' => 'yookassa', 'name' => 'ЮKassa'],
            ['code' => 'tinkoff', 'name' => 'Tinkoff / T-Bank'],
            ['code' => 'sber', 'name' => 'Сбербанк Эквайринг'],
            ['code' => 'robokassa', 'name' => 'Robokassa'],
            ['code' => 'cloudpayments', 'name' => 'CloudPayments'],
            ['code' => 'qiwi', 'name' => 'QIWI'],
            ['code' => 'stripe', 'name' => 'Stripe'],
            ['code' => 'paypal', 'name' => 'PayPal'],
            ['code' => 'nowpayments', 'name' => 'NOWPayments'],
            ['code' => 'webmoney', 'name' => 'WebMoney'],
            ['code' => 'overpay', 'name' => 'Overpay (SBP / fps)'],
            ['code' => 'overpay_card', 'name' => 'Overpay (card)'],
            ['code' => 'exnode', 'name' => 'Exnode'],
            ['code' => 'pally', 'name' => 'Pally.info (PayPalych)'],
            ['code' => 'oneplat', 'name' => '1Plat.cash'],
            ['code' => 'platega', 'name' => 'Platega'],
            ['code' => 'freekassa_sbp', 'name' => 'FreeKassa (СБП / QR)'],
            ['code' => 'freekassa_card', 'name' => 'FreeKassa (карты РФ)'],
            ['code' => 'freekassa_world_card', 'name' => 'FreeKassa (VISA / MasterCard USD)'],
            ['code' => 'friendlypay_card', 'name' => 'Friendly Pay (карты)'],
            ['code' => 'friendlypay_sbp', 'name' => 'Friendly Pay (СБП)'],
            ['code' => 'betatransfer', 'name' => 'BetaTransfer'],
            ['code' => 'manual_sbp', 'name' => 'СБП / перевод вручную'],
            ['code' => 'crypto_usdt', 'name' => 'USDT TRC20 (вручную)'],
        ];
    }

    public static function ensureSeeded(): void
    {
        try {
            PaymentGateway::all(false);
        } catch (\Throwable) {
            return; // таблица ещё не создана
        }

        foreach (self::catalog() as $g) {
            PaymentGateway::upsert($g['code'], $g['name'], false, true, []);
        }

        self::migrateLegacySettings();
    }

    private static function migrateLegacySettings(): void
    {
        // ЮKassa
        self::fillIfEmpty('yookassa', [
            'shop_id' => pay_cfg('pay_yookassa_shop_id', 'YOOKASSA_SHOP_ID'),
            'secret_key' => pay_cfg('pay_yookassa_secret_key', 'YOOKASSA_SECRET_KEY'),
            'return_url' => pay_cfg('pay_yookassa_return_url', 'YOOKASSA_RETURN_URL', app_url('/account/orders')),
        ], setting('pay_yookassa_on', '1') !== '0');

        $fk = [
            'shop_id' => pay_cfg('pay_freekassa_shop_id', 'FREEKASSA_SHOP_ID'),
            'api_key' => pay_cfg('pay_freekassa_api_key', 'FREEKASSA_API_KEY'),
            'secret_word_2' => pay_cfg('pay_freekassa_secret_word_2', 'FREEKASSA_SECRET_WORD_2'),
            'api_base_url' => pay_cfg('pay_freekassa_api_base', 'FREEKASSA_API_BASE', 'https://api.fk.life/v1'),
        ];
        $fkOn = setting('pay_freekassa_on', '1') !== '0' && ($fk['shop_id'] !== '' && $fk['api_key'] !== '');
        foreach (['freekassa_sbp', 'freekassa_card', 'freekassa_world_card'] as $code) {
            self::fillIfEmpty($code, $fk, $fkOn);
        }

        self::fillIfEmpty('platega', [
            'merchant_id' => pay_cfg('pay_platega_merchant_id', 'PLATEGA_MERCHANT_ID'),
            'secret' => pay_cfg('pay_platega_secret', 'PLATEGA_SECRET'),
            'api_base_url' => pay_cfg('pay_platega_api_base', 'PLATEGA_API_BASE', 'https://app.platega.io'),
            'payment_method' => pay_cfg('pay_platega_payment_method', 'PLATEGA_PAYMENT_METHOD'),
        ], setting('pay_platega_on', '1') !== '0');

        self::fillIfEmpty('nowpayments', [
            'api_key' => pay_cfg('pay_nowpayments_api_key', 'NOWPAYMENTS_API_KEY'),
            'ipn_secret' => pay_cfg('pay_nowpayments_ipn_secret', 'NOWPAYMENTS_IPN_SECRET'),
            'pay_currency' => pay_cfg('pay_nowpayments_pay_currency', 'NOWPAYMENTS_PAY_CURRENCY', 'usdttrc20'),
        ], setting('pay_nowpayments_on', '1') !== '0');

        self::fillIfEmpty('manual_sbp', [
            'phone' => setting('sbp_phone', ''),
            'comment' => setting('sbp_comment', 'Оплата заказа #{order_id}'),
        ], setting('pay_manual_sbp_on', '1') !== '0');

        self::fillIfEmpty('crypto_usdt', [
            'address' => setting('crypto_usdt_trc20', ''),
        ], setting('pay_crypto_on', '1') !== '0');
    }

    /**
     * @param array<string, string> $config
     */
    private static function fillIfEmpty(string $code, array $config, bool $enableIfKeys = false): void
    {
        $gw = PaymentGateway::findByCode($code);
        if (!$gw) {
            return;
        }
        $hasAny = false;
        foreach ($config as $v) {
            if (trim((string) $v) !== '') {
                $hasAny = true;
                break;
            }
        }
        if (!$hasAny) {
            return;
        }
        // Не трогаем уже настроенные записи
        if (!empty($gw['config'])) {
            return;
        }
        $merged = PaymentGateway::mergeConfig($gw['config'], $config);
        $enabled = $enableIfKeys && $hasAny;
        $st = \App\Core\Database::pdo()->prepare(
            'UPDATE payment_gateways SET config = ?, enabled = ?, updated_at = ? WHERE id = ?'
        );
        $st->execute([
            json_encode($merged, JSON_UNESCAPED_UNICODE),
            $enabled ? 1 : 0,
            now_dt(),
            (int) $gw['id'],
        ]);
    }
}
