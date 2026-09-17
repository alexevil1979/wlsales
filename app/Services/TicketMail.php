<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Mailer;

/**
 * Письма по тикетам — как SupportTicketOpened / SupportAdminReply в fullvpnservice.
 */
final class TicketMail
{
    public static function notifyOpened(array $ticket, string $message, string $userEmail, string $userName = ''): void
    {
        if (setting('mail_notify_ticket_opened', '1') === '0') {
            return;
        }
        if ($userEmail === '') {
            return;
        }
        $id = (int) ($ticket['id'] ?? 0);
        $subject = (string) ($ticket['subject'] ?? '');
        $name = $userName !== '' ? $userName : 'клиент';
        $url = app_url('/account/tickets/' . $id);
        $html = '<p>Здравствуйте, ' . e($name) . '!</p>'
            . '<p>Ваше обращение в поддержку принято.</p>'
            . '<p><strong>Тема:</strong> ' . e($subject) . '</p>'
            . '<blockquote style="border-left:3px solid #d1d5db;padding-left:12px;color:#374151">'
            . nl2br(e($message)) . '</blockquote>'
            . '<p><a href="' . e($url) . '">Открыть тикет #' . $id . '</a></p>'
            . '<p style="color:#6b7280;font-size:13px">Отвечайте в личном кабинете — мы получим сообщение.</p>';
        Mailer::send($userEmail, 'Тикет #' . $id . ': ' . $subject, $html);
    }

    public static function notifyAdminReply(array $ticket, string $reply, string $userEmail, string $userName = ''): void
    {
        if (setting('mail_notify_admin_reply', '1') === '0') {
            return;
        }
        if ($userEmail === '') {
            return;
        }
        $id = (int) ($ticket['id'] ?? 0);
        $topic = (string) ($ticket['subject'] ?? '');
        $name = $userName !== '' ? $userName : 'клиент';
        $url = app_url('/account/tickets/' . $id);
        $html = '<p>Здравствуйте, ' . e($name) . '!</p>'
            . '<p>Поддержка ответила по тикету <strong>#' . $id . '</strong>'
            . ($topic !== '' ? ' («' . e($topic) . '»)' : '') . '.</p>'
            . '<blockquote style="border-left:3px solid #f59e0b;padding-left:12px;color:#374151">'
            . nl2br(e($reply)) . '</blockquote>'
            . '<p><a href="' . e($url) . '">Ответить в личном кабинете</a></p>';
        $subj = $topic !== ''
            ? 'Ответ поддержки: ' . $topic
            : 'Ответ поддержки по тикету #' . $id;
        Mailer::send($userEmail, $subj, $html);
    }
}
