<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\AdminLog;
use App\Models\User;

final class Auth
{
    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }
        static $cached = null;
        static $cachedId = null;
        if ($cached !== null && $cachedId === (int) $id) {
            return $cached;
        }
        $user = User::findById((int) $id);
        if (!$user || (int) ($user['is_banned'] ?? 0) === 1) {
            self::logout();
            return null;
        }
        $cached = $user;
        $cachedId = (int) $id;
        return $user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && ($u['role'] ?? '') === 'admin';
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        if (($user['role'] ?? '') === 'admin') {
            AdminLog::write((int) $user['id'], 'login', client_ip());
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Войдите в аккаунт.');
            redirect('/login');
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            redirect('/account');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo 'Доступ запрещён.';
            exit;
        }
    }
}
