<?php

declare(strict_types=1);

/**
 * Front controller WL Sales
 * DocumentRoot должен указывать сюда.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

// CLI cron: php public/index.php cron
if (PHP_SAPI === 'cli') {
    $cmd = $argv[1] ?? '';
    if ($cmd === 'cron') {
        (new \App\Controllers\CronController())->run();
        exit(0);
    }
    echo "Usage: php public/index.php cron\n";
    exit(1);
}

$router = require dirname(__DIR__) . '/app/routes.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$router->dispatch($method, $uri);
