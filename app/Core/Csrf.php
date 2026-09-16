<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(?string $token = null): bool
    {
        $token ??= $_POST['_csrf'] ?? '';
        $session = $_SESSION[self::KEY] ?? '';
        return is_string($token) && $session !== '' && hash_equals($session, $token);
    }

    public static function requireValid(): void
    {
        if (!self::verify()) {
            http_response_code(419);
            flash('error', 'Сессия устарела. Обновите страницу и попробуйте снова.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}
