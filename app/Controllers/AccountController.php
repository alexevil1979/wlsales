<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Validator;
use App\Core\View;
use App\Models\Order;
use App\Models\Server;
use App\Models\Ticket;
use App\Models\User;

final class AccountController
{
    public function index(): void
    {
        $user = Auth::user();
        View::render('account/index', [
            'title' => 'Кабинет — WL Sales',
            'user' => $user,
            'orders' => Order::forUser((int) $user['id']),
            'servers' => Server::forUser((int) $user['id']),
        ]);
    }

    public function orders(): void
    {
        $user = Auth::user();
        View::render('account/orders', [
            'title' => 'Мои заказы — WL Sales',
            'orders' => Order::forUser((int) $user['id']),
        ]);
    }

    public function orderShow(string $id): void
    {
        $user = Auth::user();
        $order = Order::findById((int) $id);
        if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Заказ не найден']);
            return;
        }
        $server = null;
        foreach (Server::forUser((int) $user['id']) as $s) {
            if ((int) ($s['assigned_order_id'] ?? 0) === (int) $order['id']) {
                $server = $s;
                break;
            }
        }
        View::render('account/order_show', [
            'title' => 'Заказ #' . $order['id'] . ' — WL Sales',
            'order' => $order,
            'server' => $server,
        ]);
    }

    public function servers(): void
    {
        $user = Auth::user();
        View::render('account/servers', [
            'title' => 'Мои серверы — WL Sales',
            'servers' => Server::forUser((int) $user['id']),
        ]);
    }

    public function tickets(): void
    {
        $user = Auth::user();
        View::render('account/tickets', [
            'title' => 'Тикеты — WL Sales',
            'tickets' => Ticket::forUser((int) $user['id']),
            'orders' => Order::forUser((int) $user['id']),
        ]);
    }

    public function ticketCreate(): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $orderId = (int) ($_POST['order_id'] ?? 0) ?: null;

        $v = new Validator($_POST);
        $v->required('subject', 'Тема')->required('body', 'Сообщение')->minLen('body', 5, 'Сообщение');
        if ($v->fails()) {
            flash('error', $v->firstError());
            redirect('/account/tickets');
        }

        $tid = Ticket::create((int) $user['id'], $subject, $orderId);
        Ticket::addMessage($tid, (int) $user['id'], $body);
        flash('success', 'Тикет создан.');
        redirect('/account/tickets/' . $tid);
    }

    public function ticketShow(string $id): void
    {
        $user = Auth::user();
        $ticket = Ticket::findById((int) $id);
        if (!$ticket || (int) $ticket['user_id'] !== (int) $user['id']) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Тикет не найден']);
            return;
        }
        View::render('account/ticket_show', [
            'title' => 'Тикет #' . $ticket['id'],
            'ticket' => $ticket,
            'messages' => Ticket::messages((int) $ticket['id']),
        ]);
    }

    public function ticketReply(string $id): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $ticket = Ticket::findById((int) $id);
        if (!$ticket || (int) $ticket['user_id'] !== (int) $user['id']) {
            redirect('/account/tickets');
        }
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '') {
            flash('error', 'Введите сообщение.');
            redirect('/account/tickets/' . $id);
        }
        Ticket::addMessage((int) $id, (int) $user['id'], $body);
        Ticket::setStatus((int) $id, 'open');
        flash('success', 'Сообщение отправлено.');
        redirect('/account/tickets/' . $id);
    }

    public function passwordForm(): void
    {
        View::render('account/password', ['title' => 'Смена пароля — WL Sales']);
    }

    public function passwordUpdate(): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $current = (string) ($_POST['current'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');

        if (!password_verify($current, $user['password_hash'])) {
            flash('error', 'Текущий пароль неверен.');
            redirect('/account/password');
        }
        if (strlen($password) < 8 || $password !== $password2) {
            flash('error', 'Новый пароль: минимум 8 символов, пароли должны совпадать.');
            redirect('/account/password');
        }
        User::updatePassword((int) $user['id'], $password);
        flash('success', 'Пароль обновлён.');
        redirect('/account');
    }

    public function profileUpdate(): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $name = trim((string) ($_POST['name'] ?? ''));
        $telegram = trim((string) ($_POST['telegram'] ?? ''));
        if (mb_strlen($name) < 2) {
            flash('error', 'Укажите имя.');
            redirect('/account');
        }
        User::updateProfile((int) $user['id'], $name, $telegram);
        flash('success', 'Профиль сохранён.');
        redirect('/account');
    }
}
