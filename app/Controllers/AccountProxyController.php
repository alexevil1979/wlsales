<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Validator;
use App\Core\View;
use App\Models\ProxyDomain;
use App\Models\ProxyNode;
use App\Models\ProxySubscription;
use App\Models\Ticket;

final class AccountProxyController
{
    public function index(): void
    {
        $user = Auth::user();
        $subs = ProxySubscription::forUser((int) $user['id']);
        $domains = ProxyDomain::forUser((int) $user['id']);
        View::render('account/proxy/index', [
            'title' => 'Белый вход',
            'subscriptions' => $subs,
            'domains' => $domains,
        ], 'account');
    }

    public function show(string $id): void
    {
        $user = Auth::user();
        $sub = ProxySubscription::findById((int) $id);
        if (!$sub || (int) $sub['user_id'] !== (int) $user['id']) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Не найдено'], 'account');
            return;
        }
        View::render('account/proxy/show', [
            'title' => 'Подписка #' . $sub['id'],
            'sub' => $sub,
            'domains' => ProxyDomain::forSubscription((int) $sub['id']),
            'used' => ProxySubscription::domainCount((int) $sub['id']),
        ], 'account');
    }

    public function addDomain(string $id): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $sub = ProxySubscription::findById((int) $id);
        if (!$sub || (int) $sub['user_id'] !== (int) $user['id']) {
            redirect('/account/proxy');
        }
        if (!in_array($sub['status'], ['active'], true)) {
            flash('error', 'Подписка не активна.');
            redirect('/account/proxy/' . $id);
        }

        $domain = strtolower(trim((string) ($_POST['domain'] ?? '')));
        $origin = trim((string) ($_POST['origin_host'] ?? ''));
        $port = (int) ($_POST['origin_port'] ?? 443);
        $https = isset($_POST['origin_https']) ? 1 : 0;
        $sni = trim((string) ($_POST['origin_sni'] ?? ''));

        $v = new Validator($_POST);
        $v->required('domain', 'Домен')->required('origin_host', 'Origin');
        if ($v->fails()) {
            flash('error', $v->firstError());
            redirect('/account/proxy/' . $id);
        }
        if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
            flash('error', 'Укажите корректный FQDN.');
            redirect('/account/proxy/' . $id);
        }

        $used = ProxySubscription::domainCount((int) $sub['id']);
        if ($used >= (int) $sub['domains_limit']) {
            flash('error', 'Лимит доменов по тарифу исчерпан.');
            redirect('/account/proxy/' . $id);
        }

        try {
            ProxyDomain::create([
                'subscription_id' => (int) $sub['id'],
                'domain' => $domain,
                'origin_host' => $origin,
                'origin_port' => $port > 0 ? $port : 443,
                'origin_https' => $https,
                'origin_sni' => $sni,
            ]);
            if (!empty($sub['node_id'])) {
                ProxyNode::recalculateUsed((int) $sub['node_id']);
            }
            flash('success', 'Домен добавлен. Поставьте A-запись и дождитесь проверки DNS.');
        } catch (\Throwable $e) {
            flash('error', 'Не удалось добавить домен (возможно, он уже есть).');
        }
        redirect('/account/proxy/' . $id);
    }

    public function renew(string $id): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $sub = ProxySubscription::findById((int) $id);
        if (!$sub || (int) $sub['user_id'] !== (int) $user['id']) {
            redirect('/account/proxy');
        }
        if (!in_array($sub['status'], ['active', 'suspended'], true)) {
            flash('error', 'Продление недоступно для этого статуса.');
            redirect('/account/proxy/' . $id);
        }

        $orderId = \App\Models\Order::create(
            (int) $user['id'],
            null,
            'proxy',
            (float) $sub['price_month'],
            'awaiting_payment',
            'proxy',
            (int) $sub['id']
        );
        flash('success', 'Заказ на продление создан.');
        redirect('/pay/' . $orderId);
    }

    public function ticketHint(): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $subject = (string) ($_POST['subject'] ?? 'Белый вход: не открывается');
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '') {
            flash('error', 'Опишите проблему.');
            redirect('/account/proxy');
        }
        $tid = Ticket::create((int) $user['id'], $subject, null);
        Ticket::addMessage($tid, (int) $user['id'], $body);
        \App\Services\TelegramNotifier::notifyTicket(
            ['id' => $tid, 'subject' => $subject, 'user_email' => $user['email']],
            $body
        );
        flash('success', 'Тикет создан.');
        redirect('/account/tickets/' . $tid);
    }
}
