<?php

declare(strict_types=1);

/**
 * Front controller WL Sales
 * DocumentRoot должен указывать сюда.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

// CLI: php public/index.php cron|reset-admin
if (PHP_SAPI === 'cli') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
    $cmd = $argv[1] ?? '';
    if ($cmd === 'cron') {
        (new \App\Controllers\CronController())->run();
        exit(0);
    }
    if ($cmd === 'reset-admin') {
        if (!is_file(dirname(__DIR__) . '/app/Controllers/ResetAdminController.php')) {
            fwrite(STDERR, "ERROR: ResetAdminController.php missing — run: git pull origin main\n");
            exit(1);
        }
        (new \App\Controllers\ResetAdminController())->run();
        exit(0);
    }
    echo "Usage: php public/index.php cron|reset-admin\n";
    exit(1);
}

$router = require dirname(__DIR__) . '/app/routes.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$router->dispatch($method, $uri);
