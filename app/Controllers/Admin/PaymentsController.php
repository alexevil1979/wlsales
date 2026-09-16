<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\Payment;
use App\Services\OrderService;

final class PaymentsController
{
    public function index(): void
    {
        View::render('admin/payments/index', [
            'title' => 'Платежи',
            'payments' => Payment::allAdmin(),
            'waiting' => Payment::waitingConfirm(),
        ], 'admin');
    }

    public function confirm(string $id): void
    {
        Csrf::requireValid();
        try {
            OrderService::confirmManualPayment((int) $id);
            AdminLog::write((int) Auth::id(), 'payment_confirm', client_ip(), 'payment=' . $id);
            flash('success', 'Оплата подтверждена.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/payments');
    }
}
