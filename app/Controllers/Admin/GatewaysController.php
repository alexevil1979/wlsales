<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\Setting;
use App\Services\PaymentService;

final class GatewaysController
{
    public function index(): void
    {
        $enabled = [];
        foreach (PaymentService::gateways() as $gw) {
            $enabled[$gw->name()] = $gw->isEnabled();
        }
        View::render('admin/payments/gateways', [
            'title' => 'Платёжные системы',
            'settings' => Setting::all(),
            'enabled' => $enabled,
        ], 'admin');
    }

    public function save(): void
    {
        Csrf::requireValid();
        $keys = [
            'sbp_phone', 'sbp_comment', 'crypto_usdt_trc20', 'usd_rate',
            'pay_yookassa_shop_id', 'pay_yookassa_secret_key', 'pay_yookassa_return_url',
            'pay_freekassa_shop_id', 'pay_freekassa_api_key', 'pay_freekassa_secret_word_2', 'pay_freekassa_api_base',
            'pay_platega_merchant_id', 'pay_platega_secret', 'pay_platega_api_base', 'pay_platega_payment_method',
            'pay_nowpayments_api_key', 'pay_nowpayments_ipn_secret', 'pay_nowpayments_pay_currency',
        ];
        $pairs = [];
        foreach ($keys as $k) {
            // пустой секрет не затираем, если пришёл placeholder
            if (str_contains($k, 'secret') || str_contains($k, 'api_key') || str_contains($k, 'password')) {
                $val = (string) ($_POST[$k] ?? '');
                if ($val === '' || $val === '********') {
                    continue;
                }
            }
            $pairs[$k] = (string) ($_POST[$k] ?? '');
        }
        // чекбоксы включения (опционально скрыть даже при ключах)
        foreach (['pay_yookassa_on', 'pay_freekassa_on', 'pay_platega_on', 'pay_nowpayments_on', 'pay_manual_sbp_on', 'pay_crypto_on'] as $flag) {
            $pairs[$flag] = !empty($_POST[$flag]) ? '1' : '0';
        }
        Setting::setMany($pairs);
        AdminLog::write((int) Auth::id(), 'payment_gateways_save', client_ip());
        flash('success', 'Платёжные системы сохранены.');
        redirect('/admin/payments/gateways');
    }
}
