<?php

declare(strict_types=1);

namespace App\Services\Payment;

/**
 * Поля config для админки — как секции PaymentGatewayResource в fullvpnservice.
 */
final class GatewayConfigSchema
{
    /**
     * @return list<array{key:string,label:string,type?:string,default?:string,secret?:bool,help?:string}>
     */
    public static function fields(string $code): array
    {
        $code = strtolower(trim($code));

        if (str_starts_with($code, 'freekassa')) {
            return [
                ['key' => 'api_base_url', 'label' => 'API base URL', 'default' => 'https://api.fk.life/v1'],
                ['key' => 'api_create_path', 'label' => 'Create-order path', 'default' => '/orders/create'],
                ['key' => 'shop_id', 'label' => 'Shop ID (merchant ID)'],
                ['key' => 'api_key', 'label' => 'API key', 'secret' => true],
                ['key' => 'secret_word_2', 'label' => 'Secret word 2 (webhook)', 'secret' => true],
                ['key' => 'payment_method', 'label' => 'Payment method ID (i)', 'help' => 'Пусто = по коду (СБП 44 / карты 36 / world 32)'],
                ['key' => 'currency', 'label' => 'Currency', 'default' => 'RUB'],
                ['key' => 'fallback_ip', 'label' => 'Fallback payer IP'],
                ['key' => 'notification_url', 'label' => 'Notification URL override'],
                ['key' => 'success_url', 'label' => 'Success URL'],
                ['key' => 'failure_url', 'label' => 'Failure URL'],
                ['key' => 'api_timeout_sec', 'label' => 'API timeout (sec)', 'default' => '25'],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
            ];
        }

        return match ($code) {
            'yookassa' => [
                ['key' => 'shop_id', 'label' => 'shopId'],
                ['key' => 'secret_key', 'label' => 'secretKey', 'secret' => true],
                ['key' => 'return_url', 'label' => 'Return URL'],
                ['key' => 'webhook_secret', 'label' => 'Webhook secret (optional)', 'secret' => true],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
            ],
            'nowpayments' => [
                ['key' => 'api_key', 'label' => 'API key', 'secret' => true],
                ['key' => 'ipn_secret', 'label' => 'IPN secret', 'secret' => true],
                ['key' => 'pay_currency', 'label' => 'Pay currency', 'default' => 'usdttrc20'],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
            ],
            'platega' => [
                ['key' => 'api_base_url', 'label' => 'API base URL', 'default' => 'https://app.platega.io'],
                ['key' => 'api_create_path', 'label' => 'Create-payment path', 'default' => '/v2/transaction/process'],
                ['key' => 'merchant_id', 'label' => 'X-MerchantId'],
                ['key' => 'secret', 'label' => 'X-Secret', 'secret' => true],
                ['key' => 'command', 'label' => 'Command', 'default' => 'process'],
                ['key' => 'currency', 'label' => 'Currency', 'default' => 'RUB'],
                ['key' => 'payment_method', 'label' => 'Payment method (optional)'],
                ['key' => 'return_url', 'label' => 'Success return URL'],
                ['key' => 'failed_url', 'label' => 'Failure URL'],
                ['key' => 'api_timeout_sec', 'label' => 'API timeout (sec)', 'default' => '25'],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
            ],
            'overpay', 'overpay_card' => [
                ['key' => 'merchant_id', 'label' => 'Merchant ID'],
                ['key' => 'secret', 'label' => 'Secret', 'secret' => true],
                ['key' => 'api_username', 'label' => 'API username (Basic, optional)'],
                ['key' => 'api_password', 'label' => 'API password', 'secret' => true],
                ['key' => 'certificate_path', 'label' => 'Certificate path', 'default' => 'app/private/overpay/client.pfx'],
                ['key' => 'certificate_password', 'label' => 'Certificate password', 'secret' => true],
                ['key' => 'api_base_url', 'label' => 'API base URL', 'default' => 'https://api-pay.overpay.io'],
                ['key' => 'payment_method', 'label' => 'Payment method (card|fps)', 'default' => $code === 'overpay_card' ? 'card' : 'fps'],
                ['key' => 'api_create_path', 'label' => 'API create path', 'default' => '/orders/preflight'],
                ['key' => 'project_id', 'label' => 'Project ID'],
                ['key' => 'return_url', 'label' => 'Return URL'],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
            ],
            'exnode' => [
                ['key' => 'api_base_url', 'label' => 'API base URL', 'default' => 'https://my.exnode.io'],
                ['key' => 'api_create_path', 'label' => 'API create path', 'default' => '/api/crypto/invoice/create'],
                ['key' => 'api_public', 'label' => 'ApiPublic'],
                ['key' => 'api_private', 'label' => 'ApiPrivate', 'secret' => true],
                ['key' => 'merchant_uuid', 'label' => 'Merchant UUID'],
                ['key' => 'token', 'label' => 'Token', 'default' => 'USDTTRC'],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
            ],
            'pally' => [
                ['key' => 'api_key', 'label' => 'Bearer API key', 'secret' => true],
                ['key' => 'api_base_url', 'label' => 'API base URL', 'default' => 'https://pally.info'],
                ['key' => 'api_create_path', 'label' => 'API create path', 'default' => '/api/v1/bill/create'],
                ['key' => 'webhook_secret', 'label' => 'Webhook secret', 'secret' => true],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
            ],
            'oneplat' => [
                ['key' => 'mode', 'label' => 'Mode (form|invoice)', 'default' => 'form'],
                ['key' => 'api_base_url', 'label' => 'API base URL', 'default' => 'https://api.1payment.com'],
                ['key' => 'partner_id', 'label' => 'Partner ID'],
                ['key' => 'project_id', 'label' => 'Project ID'],
                ['key' => 'api_key', 'label' => 'API key', 'secret' => true],
                ['key' => 'signature_key', 'label' => 'Webhook signature key', 'secret' => true],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
            ],
            'friendlypay_card', 'friendlypay_sbp', 'friendlypay' => [
                ['key' => 'api_base_url', 'label' => 'API base URL', 'default' => 'https://pay.friendlypay.io/api'],
                ['key' => 'api_token', 'label' => 'API token (x-fp-api-token)', 'secret' => true],
                ['key' => 'secret_key', 'label' => 'Secret key (HMAC)', 'secret' => true],
                ['key' => 'transaction_type', 'label' => 'Transaction type (card|sbp)', 'default' => str_contains($code, 'sbp') ? 'sbp' : 'card'],
                ['key' => 'currency', 'label' => 'Currency', 'default' => 'RUB'],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
            ],
            'betatransfer' => [
                ['key' => 'api_base_url', 'label' => 'API base URL', 'default' => 'https://merchant.betatransfer.io/api'],
                ['key' => 'payment_path', 'label' => 'Payment path', 'default' => '/payment'],
                ['key' => 'token', 'label' => 'API public token', 'secret' => true],
                ['key' => 'secret_key', 'label' => 'API secret key', 'secret' => true],
                ['key' => 'currency', 'label' => 'Currency', 'default' => 'RUB'],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
            ],
            'manual_sbp' => [
                ['key' => 'phone', 'label' => 'Телефон СБП'],
                ['key' => 'comment', 'label' => 'Комментарий', 'default' => 'Оплата заказа #{order_id}'],
            ],
            'crypto_usdt' => [
                ['key' => 'address', 'label' => 'USDT TRC20 адрес'],
            ],
            'tinkoff', 'sber', 'robokassa', 'cloudpayments', 'qiwi', 'stripe', 'paypal', 'webmoney' => [
                ['key' => 'shop_id', 'label' => 'Shop / Merchant ID'],
                ['key' => 'secret_key', 'label' => 'Secret / Password', 'secret' => true],
                ['key' => 'api_key', 'label' => 'API key (если нужен)', 'secret' => true],
                ['key' => 'return_url', 'label' => 'Return URL'],
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
                ['key' => 'note', 'label' => 'Заметка / доп. параметр'],
            ],
            default => [
                ['key' => 'extra_commission_percent', 'label' => 'Доп. комиссия %', 'default' => '0'],
                ['key' => 'api_key', 'label' => 'API key', 'secret' => true],
                ['key' => 'secret_key', 'label' => 'Secret key', 'secret' => true],
                ['key' => 'shop_id', 'label' => 'Shop ID'],
            ],
        };
    }

    public static function webhookHint(string $code): string
    {
        $code = strtolower(trim($code));
        $map = [
            'yookassa' => '/webhooks/yookassa',
            'freekassa' => '/webhooks/freekassa',
            'freekassa_sbp' => '/webhooks/freekassa',
            'freekassa_card' => '/webhooks/freekassa',
            'freekassa_world_card' => '/webhooks/freekassa',
            'platega' => '/webhooks/platega',
            'nowpayments' => '/webhooks/nowpayments',
            'overpay' => '/webhooks/overpay',
            'overpay_card' => '/webhooks/overpay',
            'friendlypay' => '/webhooks/friendlypay',
            'friendlypay_card' => '/webhooks/friendlypay',
            'friendlypay_sbp' => '/webhooks/friendlypay',
            'pally' => '/webhooks/pally',
            'oneplat' => '/webhooks/oneplat',
            'exnode' => '/webhooks/exnode',
            'betatransfer' => '/webhooks/betatransfer',
        ];
        return isset($map[$code]) ? app_url($map[$code]) : '';
    }
}
