<?php

declare(strict_types=1);

use App\Core\Env;

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function store_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function money(float|string $amount): string
{
    return number_format((float) $amount, 0, ',', ' ') . ' ₽';
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function app_url(string $path = ''): string
{
    $base = rtrim((string) Env::get('APP_URL', ''), '/');
    if ($path === '') {
        return $base;
    }
    return $base . '/' . ltrim($path, '/');
}

function setting(string $key, string $default = ''): string
{
    return \App\Models\Setting::get($key, $default);
}

function tariff_label(string $tariff): string
{
    return match ($tariff) {
        'rent' => 'Аренда / мес',
        'inst2' => 'Рассрочка 2 недели',
        'inst4' => 'Рассрочка 4 недели',
        'forever' => 'Выкуп навсегда',
        default => $tariff,
    };
}

function order_status_label(string $status): string
{
    return match ($status) {
        'new' => 'Новый',
        'awaiting_payment' => 'Ожидает оплаты',
        'paid' => 'Оплачен',
        'delivered' => 'Выдан',
        'cancelled' => 'Отменён',
        'refund' => 'Возврат',
        'pending_stock' => 'Под заказ',
        default => $status,
    };
}

function product_status_label(string $status): string
{
    return match ($status) {
        'available' => 'В наличии',
        'reserved' => 'Зарезервирован',
        'sold' => 'Продан',
        'hidden' => 'Скрыт',
        'preorder' => 'Под заказ',
        default => $status,
    };
}

function encrypt_secret(string $plain): string
{
    $key = hash('sha256', (string) Env::get('ENCRYPT_KEY', 'fallback-key'), true);
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}

function decrypt_secret(?string $encoded): string
{
    if ($encoded === null || $encoded === '') {
        return '';
    }
    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 17) {
        return '';
    }
    $key = hash('sha256', (string) Env::get('ENCRYPT_KEY', 'fallback-key'), true);
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}

function now_dt(): string
{
    return date('Y-m-d H:i:s');
}
