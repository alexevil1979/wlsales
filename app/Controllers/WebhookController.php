<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\YooKassaGateway;

final class WebhookController
{
    public function yookassa(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo 'bad json';
            return;
        }

        $gw = new YooKassaGateway();
        if (!$gw->isEnabled()) {
            http_response_code(503);
            echo 'disabled';
            return;
        }

        try {
            $gw->handleWebhook($payload);
            http_response_code(200);
            echo 'ok';
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'error';
        }
    }
}
