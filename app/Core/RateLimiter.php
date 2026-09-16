<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\LoginAttempt;

final class RateLimiter
{
    public static function tooManyLogins(string $ip, int $max = 8, int $minutes = 15): bool
    {
        return LoginAttempt::countRecent($ip, $minutes) >= $max;
    }

    public static function hitLogin(string $ip, string $email): void
    {
        LoginAttempt::record($ip, $email);
    }

    public static function clearOld(): void
    {
        LoginAttempt::purgeOlderThan(2);
    }
}
