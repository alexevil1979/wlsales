<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\Setting;
use App\Services\TelegramNotifier;

final class SettingsController
{
    public function index(): void
    {
        View::render('admin/settings/index', [
            'title' => 'Настройки',
            'settings' => Setting::all(),
        ], 'admin');
    }

    public function save(): void
    {
        Csrf::requireValid();

        if (!empty($_POST['tg_test'])) {
            try {
                // сначала сохраним токены если пришли
                $this->persist($_POST);
                TelegramNotifier::sendTest();
                flash('success', 'Тестовое сообщение отправлено в Telegram.');
            } catch (\Throwable $e) {
                flash('error', $e->getMessage());
            }
            redirect('/admin/settings');
        }

        $this->persist($_POST);
        AdminLog::write((int) Auth::id(), 'settings_save', client_ip());
        flash('success', 'Настройки сохранены.');
        redirect('/admin/settings');
    }

    /** @param array<string, mixed> $post */
    private function persist(array $post): void
    {
        $keys = [
            'site_telegram', 'site_email', 'site_phone', 'requisites',
            'sbp_phone', 'sbp_comment', 'crypto_usdt_trc20', 'usd_rate',
            'tg_bot_token', 'tg_admin_chat_id',
            'offer_html', 'privacy_html',
        ];
        $pairs = [];
        foreach ($keys as $k) {
            $pairs[$k] = (string) ($post[$k] ?? '');
        }
        foreach (['tg_notify_registrations', 'tg_notify_payments', 'tg_notify_deliveries', 'tg_notify_tickets'] as $flag) {
            $pairs[$flag] = !empty($post[$flag]) ? '1' : '0';
        }
        Setting::setMany($pairs);
    }
}
