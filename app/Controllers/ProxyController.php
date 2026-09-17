<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Order;
use App\Models\ProxyPlan;
use App\Models\ProxySubscription;

final class ProxyController
{
    public function index(): void
    {
        if (!\App\Core\Database::hasTable('proxy_plans')) {
            View::render('proxy/index', [
                'title' => 'Белый вход для сайта — WL Sales',
                'description' => 'Вход с проверенного публичного IP: DNS A → наш IP:443 → ваш origin.',
                'plans' => [],
                'domainsWanted' => 1,
                'suggested' => null,
                'missingSchema' => true,
            ]);
            return;
        }
        $plans = ProxyPlan::active();
        $domainsWanted = isset($_GET['domains']) ? max(1, (int) $_GET['domains']) : 1;
        $suggested = ProxyPlan::suggestForDomains($domainsWanted);

        View::render('proxy/index', [
            'title' => 'Белый вход для сайта — WL Sales',
            'description' => 'Вход с проверенного публичного IP: DNS A → наш IP:443 → ваш origin. Несколько доменов на одном IP.',
            'plans' => $plans,
            'domainsWanted' => $domainsWanted,
            'suggested' => $suggested,
        ]);
    }

    public function checkout(string $planId): void
    {
        Csrf::requireValid();
        Auth::requireLogin();

        $plan = ProxyPlan::findById((int) $planId);
        if (!$plan || !(int) $plan['active']) {
            flash('error', 'Тариф недоступен.');
            redirect('/proxy');
        }

        $subId = ProxySubscription::create((int) Auth::id(), (int) $plan['id'], 'awaiting_payment');
        $orderId = Order::create(
            (int) Auth::id(),
            null,
            'proxy',
            (float) $plan['price_month'],
            'awaiting_payment',
            'proxy',
            $subId
        );

        flash('success', 'Заказ белого входа создан. Оплатите, чтобы активировать подписку.');
        redirect('/pay/' . $orderId);
    }
}
