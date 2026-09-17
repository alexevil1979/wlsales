<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FreeKassaGateway;
use App\Services\NowPaymentsGateway;
use App\Services\PlategaGateway;
use App\Services\YooKassaGateway;

final class WebhookController
{
    public function yookassa(): void
    {
        $this->handleJson(new YooKassaGateway());
    }

    public function freekassa(): void
    {
        $payload = $_POST;
        if ($payload === []) {
            $raw = file_get_contents('php://input') ?: '';
            $json = json_decode($raw, true);
            $payload = is_array($json) ? $json : [];
        }
        try {
            (new FreeKassaGateway('freekassa_sbp'))->handleWebhook($payload);
            http_response_code(200);
            echo 'YES';
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'ERROR';
        }
    }

    public function nowpayments(): void
    {
        $this->handleJson(new NowPaymentsGateway());
    }

    public function platega(): void
    {
        $this->handleJson(new PlategaGateway());
    }

    private function handleJson(object $gw): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo 'bad json';
            return;
        }
        if (method_exists($gw, 'isEnabled') && !$gw->isEnabled()) {
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
