<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\PaymentGateway;
use App\Services\Payment\GatewayConfigSchema;
use App\Services\Payment\GatewaySeeder;
use App\Services\PaymentService;

final class GatewaysController
{
    public function index(): void
    {
        GatewaySeeder::ensureSeeded();
        $drivers = PaymentService::driverMap();
        View::render('admin/payments/gateways', [
            'title' => 'Платёжные шлюзы',
            'gateways' => PaymentGateway::all(true),
            'drivers' => $drivers,
            'breadcrumbs' => [
                ['label' => 'Админ', 'href' => '/admin'],
                ['label' => 'Платёжные шлюзы'],
            ],
        ], 'admin');
    }

    public function edit(string $id): void
    {
        GatewaySeeder::ensureSeeded();
        $gw = PaymentGateway::findById((int) $id);
        if (!$gw) {
            flash('error', 'Шлюз не найден.');
            redirect('/admin/payments/gateways');
        }
        View::render('admin/payments/gateway_edit', [
            'title' => $gw['name'],
            'gw' => $gw,
            'fields' => GatewayConfigSchema::fields((string) $gw['code']),
            'webhook' => GatewayConfigSchema::webhookHint((string) $gw['code']),
            'hasDriver' => isset(PaymentService::driverMap()[strtolower((string) $gw['code'])]),
            'breadcrumbs' => [
                ['label' => 'Админ', 'href' => '/admin'],
                ['label' => 'Платёжные шлюзы', 'href' => '/admin/payments/gateways'],
                ['label' => (string) $gw['code']],
            ],
        ], 'admin');
    }

    public function save(string $id): void
    {
        Csrf::requireValid();
        $gw = PaymentGateway::findById((int) $id);
        if (!$gw) {
            redirect('/admin/payments/gateways');
        }

        $name = trim((string) ($_POST['name'] ?? $gw['name']));
        $enabled = !empty($_POST['enabled']);
        $testMode = !empty($_POST['test_mode']);
        $minAmount = (float) ($_POST['min_amount_rub'] ?? 0);

        $config = [];
        foreach (GatewayConfigSchema::fields((string) $gw['code']) as $field) {
            $key = $field['key'];
            $config[$key] = (string) ($_POST['config'][$key] ?? '');
        }

        PaymentGateway::update((int) $id, $name !== '' ? $name : (string) $gw['name'], $enabled, $testMode, $minAmount, $config);

        // синхронизация ручных реквизитов в settings (для совместимости)
        if ($gw['code'] === 'manual_sbp') {
            if (($config['phone'] ?? '') !== '' && $config['phone'] !== '********') {
                \App\Models\Setting::set('sbp_phone', $config['phone']);
            }
            if (($config['comment'] ?? '') !== '') {
                \App\Models\Setting::set('sbp_comment', $config['comment']);
            }
        }
        if ($gw['code'] === 'crypto_usdt' && ($config['address'] ?? '') !== '') {
            \App\Models\Setting::set('crypto_usdt_trc20', $config['address']);
        }

        AdminLog::write((int) Auth::id(), 'payment_gateway_save:' . $gw['code'], client_ip());
        flash('success', 'Шлюз сохранён.');
        redirect('/admin/payments/gateways/' . $id);
    }

    public function toggle(string $id): void
    {
        Csrf::requireValid();
        $gw = PaymentGateway::findById((int) $id);
        if ($gw) {
            PaymentGateway::setEnabled((int) $id, empty($gw['enabled']));
            AdminLog::write((int) Auth::id(), 'payment_gateway_toggle:' . $gw['code'], client_ip());
            flash('success', ($gw['enabled'] ? 'Выключен' : 'Включён') . ': ' . $gw['name']);
        }
        redirect('/admin/payments/gateways');
    }
}
