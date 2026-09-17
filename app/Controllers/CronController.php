<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\RateLimiter;
use App\Models\Order;
use App\Services\ProxyService;

final class CronController
{
    public function run(): void
    {
        $isCli = PHP_SAPI === 'cli';
        $token = $_GET['token'] ?? '';
        $expected = Env::get('APP_KEY', '');
        if (!$isCli && (!is_string($token) || $token === '' || !hash_equals((string) $expected, $token))) {
            http_response_code(403);
            echo 'forbidden';
            return;
        }

        $n = Order::expireUnpaid(48);
        RateLimiter::clearOld();
        $renew = ProxyService::processRenewals();
        echo "expired={$n}; proxy_reminded={$renew['reminded']}; proxy_suspended={$renew['suspended']}\n";
    }
}
