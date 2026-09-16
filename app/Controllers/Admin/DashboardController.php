<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\User;

final class DashboardController
{
    public function index(): void
    {
        View::render('admin/dashboard', [
            'title' => 'Админ — дашборд',
            'newOrders' => Order::countByStatus('new') + Order::countByStatus('awaiting_payment'),
            'awaitingPayment' => Order::countByStatus('awaiting_payment'),
            'awaitingDelivery' => Order::countByStatus('paid') + Order::countByStatus('pending_stock'),
            'revenue30' => Order::revenueLastDays(30),
            'users' => User::count(),
            'waitingPayments' => Payment::waitingConfirm(),
            'openTickets' => count(array_filter(Ticket::allAdmin(), static fn ($t) => $t['status'] === 'open')),
            'logs' => AdminLog::recent(15),
        ], 'admin');
    }
}
