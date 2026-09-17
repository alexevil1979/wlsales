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
        if (!\App\Core\Database::hasTable('payment_gateways')) {
            View::render('admin/payments/gateways_missing', [
                'title' => 'Платёжные шлюзы',
                'breadcrumbs' => [
                    ['label' => 'Платёжные шлюзы'],
                ],
            ], 'admin');
            return;
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        $enabled = (string) ($_GET['enabled'] ?? '');
        $test = (string) ($_GET['test_mode'] ?? '');
        $type = (string) ($_GET['type'] ?? '');

        View::render('admin/payments/gateways', [
            'title' => 'Платёжные шлюзы',
            'gateways' => PaymentGateway::adminList($q, $enabled, $test, $type),
            'q' => $q,
            'filterEnabled' => $enabled,
            'filterTest' => $test,
            'filterType' => $type,
            'csrf' => Csrf::token(),
            'breadcrumbs' => [
                ['label' => 'Платёжные шлюзы', 'href' => '/admin/payments/gateways'],
                ['label' => 'Список'],
            ],
        ], 'admin');
    }

    public function createForm(): void
    {
        View::render('admin/payments/gateway_create', [
            'title' => 'Создать шлюз',
            'breadcrumbs' => [
                ['label' => 'Платёжные шлюзы', 'href' => '/admin/payments/gateways'],
                ['label' => 'Создать'],
            ],
        ], 'admin');
    }

    public function create(): void
    {
        Csrf::requireValid();
        $code = strtolower(trim((string) ($_POST['code'] ?? '')));
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($code === '' || $name === '') {
            flash('error', 'Укажите код и имя.');
            redirect('/admin/payments/gateways/create');
        }
        try {
            $id = PaymentGateway::create($code, $name, !empty($_POST['enabled']), !empty($_POST['test_mode']));
            AdminLog::write((int) Auth::id(), 'payment_gateway_create:' . $code, client_ip());
            flash('success', 'Шлюз создан.');
            redirect('/admin/payments/gateways/' . $id);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/admin/payments/gateways/create');
        }
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
                ['label' => 'Платёжные шлюзы', 'href' => '/admin/payments/gateways'],
                ['label' => 'Изменить'],
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
        if (isset($_POST['boosty_page_slug'])) {
            $config['boosty_page_slug'] = trim((string) $_POST['boosty_page_slug']);
        }

        PaymentGateway::update((int) $id, $name !== '' ? $name : (string) $gw['name'], $enabled, $testMode, $minAmount, $config);

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
        $wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if (!Csrf::verify()) {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(419);
                echo json_encode(['ok' => false, 'message' => 'csrf'], JSON_UNESCAPED_UNICODE);
                return;
            }
            Csrf::requireValid();
            return;
        }

        $gw = PaymentGateway::findById((int) $id);
        if (!$gw) {
            if ($wantsJson) {
                $this->toggleRespond(false, 'not found');
                return;
            }
            redirect('/admin/payments/gateways');
            return;
        }
        $field = (string) ($_POST['field'] ?? 'enabled');
        if (!isset($_POST['value'])) {
            $value = $field === 'test_mode' ? empty($gw['test_mode']) : empty($gw['enabled']);
        } else {
            $raw = $_POST['value'];
            $value = $raw === true || $raw === 1 || $raw === '1' || $raw === 'true' || $raw === 'on';
        }

        if ($field === 'test_mode') {
            PaymentGateway::setTestMode((int) $id, $value);
        } else {
            PaymentGateway::setEnabled((int) $id, $value);
        }
        AdminLog::write((int) Auth::id(), 'payment_gateway_toggle:' . $gw['code'] . ':' . $field, client_ip());

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'field' => $field, 'value' => $value], JSON_UNESCAPED_UNICODE);
            return;
        }
        redirect('/admin/payments/gateways');
    }

    private function toggleRespond(bool $ok, string $msg): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 400);
        echo json_encode(['ok' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    }
}
