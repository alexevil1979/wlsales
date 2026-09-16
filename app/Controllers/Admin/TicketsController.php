<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Ticket;

final class TicketsController
{
    public function index(): void
    {
        View::render('admin/tickets/index', [
            'title' => 'Тикеты',
            'tickets' => Ticket::allAdmin(),
        ], 'admin');
    }

    public function show(string $id): void
    {
        $ticket = Ticket::findById((int) $id);
        if (!$ticket) {
            redirect('/admin/tickets');
        }
        View::render('admin/tickets/show', [
            'title' => 'Тикет #' . $ticket['id'],
            'ticket' => $ticket,
            'messages' => Ticket::messages((int) $ticket['id']),
        ], 'admin');
    }

    public function reply(string $id): void
    {
        Csrf::requireValid();
        $body = trim((string) ($_POST['body'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'answered');
        if ($body !== '') {
            Ticket::addMessage((int) $id, (int) Auth::id(), $body);
        }
        if (in_array($status, ['open', 'answered', 'closed'], true)) {
            Ticket::setStatus((int) $id, $status);
        }
        flash('success', 'Ответ сохранён.');
        redirect('/admin/tickets/' . $id);
    }
}
