<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\Setting;

final class MailController
{
    public function index(): void
    {
        View::render('admin/mail/index', [
            'title' => 'Почта (SMTP)',
            'settings' => Setting::all(),
        ], 'admin');
    }

    public function save(): void
    {
        Csrf::requireValid();

        $keys = [
            'mail_smtp_host', 'mail_smtp_port', 'mail_smtp_secure',
            'mail_smtp_user', 'mail_from', 'mail_from_name',
            'mail_test_to',
        ];
        $pairs = [];
        foreach ($keys as $k) {
            $pairs[$k] = trim((string) ($_POST[$k] ?? ''));
        }
        $pass = (string) ($_POST['mail_smtp_pass'] ?? '');
        if ($pass !== '' && $pass !== '********') {
            $pairs['mail_smtp_pass'] = $pass;
        }
        $pairs['mail_smtp_on'] = !empty($_POST['mail_smtp_on']) ? '1' : '0';
        $pairs['mail_notify_ticket_opened'] = !empty($_POST['mail_notify_ticket_opened']) ? '1' : '0';
        $pairs['mail_notify_admin_reply'] = !empty($_POST['mail_notify_admin_reply']) ? '1' : '0';

        Setting::setMany($pairs);
        AdminLog::write((int) Auth::id(), 'mail_settings_save', client_ip());

        if (!empty($_POST['mail_test'])) {
            $to = $pairs['mail_test_to'] !== ''
                ? $pairs['mail_test_to']
                : (string) (Auth::user()['email'] ?? '');
            if ($to === '') {
                flash('error', 'Укажите email для теста.');
                redirect('/admin/mail');
            }
            $ok = Mailer::send(
                $to,
                'WL Sales: тест SMTP',
                '<p>Тестовое письмо из админки WL Sales. Если вы его получили — SMTP работает.</p>'
            );
            if ($ok) {
                flash('success', 'Настройки сохранены. Тест отправлен на ' . $to);
            } else {
                flash('error', 'Настройки сохранены, но тест не отправился. Проверьте SMTP / логи.');
            }
            redirect('/admin/mail');
        }

        flash('success', 'Настройки почты сохранены.');
        redirect('/admin/mail');
    }
}
