<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;

/**
 * Уведомления админу в Telegram — по образцу fullvpnservice AdminTelegramNotifier.
 */
final class TelegramNotifier
{
    public static function notifyRegistration(array $user): void
    {
        if (!self::flag('tg_notify_registrations', true)) {
            return;
        }
        self::send(
            "🆕 Новая регистрация\n"
            . 'ID: ' . (int) ($user['id'] ?? 0) . "\n"
            . 'Имя: ' . ((string) ($user['name'] ?? '—')) . "\n"
            . 'Email: ' . ((string) ($user['email'] ?? '—')) . "\n"
            . 'Telegram: ' . ((string) ($user['telegram'] ?? '—') ?: '—')
        );
    }

    public static function notifyPayment(array $order, string $provider = ''): void
    {
        if (!self::flag('tg_notify_payments', true)) {
            return;
        }
        self::send(
            "✅ Оплата получена\n"
            . 'Заказ #' . (int) ($order['id'] ?? 0) . "\n"
            . 'Клиент: ' . ((string) ($order['user_email'] ?? $order['user_id'] ?? '—')) . "\n"
            . 'Лот: ' . ((string) ($order['product_title'] ?? $order['product_id'] ?? '—')) . "\n"
            . 'Тариф: ' . tariff_label((string) ($order['tariff'] ?? '')) . "\n"
            . 'Сумма: ' . money((float) ($order['amount'] ?? 0)) . "\n"
            . 'Шлюз: ' . ($provider !== '' ? $provider : ((string) ($order['payment_provider'] ?? '—')))
        );
    }

    public static function notifyDelivered(array $order, string $ip = ''): void
    {
        if (!self::flag('tg_notify_deliveries', true)) {
            return;
        }
        self::send(
            "📦 Доступы выданы\n"
            . 'Заказ #' . (int) ($order['id'] ?? 0) . "\n"
            . 'Клиент: ' . ((string) ($order['user_email'] ?? '—')) . "\n"
            . 'IP: ' . ($ip !== '' ? $ip : '—')
        );
    }

    public static function notifyTicket(array $ticket, string $preview = ''): void
    {
        if (!self::flag('tg_notify_tickets', true)) {
            return;
        }
        $text = "🎫 Тикет #" . (int) ($ticket['id'] ?? 0) . "\n"
            . 'Тема: ' . ((string) ($ticket['subject'] ?? '—')) . "\n"
            . 'Клиент: ' . ((string) ($ticket['user_email'] ?? $ticket['user_id'] ?? '—'));
        if ($preview !== '') {
            $text .= "\n" . mb_substr($preview, 0, 300);
        }
        self::send($text);
    }

    public static function sendTest(): bool
    {
        return self::send('✅ Test admin notification WL Sales: connection OK.', true);
    }

    public static function send(string $text, bool $throwOnFailure = false): bool
    {
        $token = self::token();
        $chatId = self::chatId();
        if ($token === '' || $chatId === '') {
            if ($throwOnFailure) {
                throw new \RuntimeException('TELEGRAM_BOT_TOKEN / TELEGRAM_ADMIN_CHAT_ID не заданы');
            }
            return false;
        }

        $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => true,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $ok = $code >= 200 && $code < 300;
        if (!$ok && $throwOnFailure) {
            throw new \RuntimeException('Telegram API error: HTTP ' . $code . ' ' . (string) $raw);
        }
        return $ok;
    }

    private static function token(): string
    {
        $fromEnv = trim((string) Env::get('TELEGRAM_BOT_TOKEN', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }
        return trim(setting('tg_bot_token', ''));
    }

    private static function chatId(): string
    {
        $fromEnv = trim((string) Env::get('TELEGRAM_ADMIN_CHAT_ID', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }
        return trim(setting('tg_admin_chat_id', ''));
    }

    private static function flag(string $key, bool $default): bool
    {
        $v = setting($key, $default ? '1' : '0');
        return in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
    }
}
