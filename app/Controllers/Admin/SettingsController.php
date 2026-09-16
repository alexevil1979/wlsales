<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\Setting;

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
        $keys = [
            'site_telegram', 'site_email', 'site_phone', 'requisites',
            'sbp_phone', 'sbp_comment', 'crypto_usdt_trc20',
            'offer_html', 'privacy_html',
        ];
        $pairs = [];
        foreach ($keys as $k) {
            $pairs[$k] = (string) ($_POST[$k] ?? '');
        }
        Setting::setMany($pairs);
        AdminLog::write((int) Auth::id(), 'settings_save', client_ip());
        flash('success', 'Настройки сохранены.');
        redirect('/admin/settings');
    }
}
