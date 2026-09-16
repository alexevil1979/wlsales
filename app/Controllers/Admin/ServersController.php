<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\Product;
use App\Models\Server;

final class ServersController
{
    public function index(): void
    {
        View::render('admin/servers/index', [
            'title' => 'Инвентарь серверов',
            'servers' => Server::allAdmin(),
            'products' => Product::allAdmin(),
        ], 'admin');
    }

    public function create(): void
    {
        Csrf::requireValid();
        $secret = trim((string) ($_POST['secret'] ?? ''));
        Server::create([
            'product_id' => (int) ($_POST['product_id'] ?? 0) ?: null,
            'hostname' => trim((string) ($_POST['hostname'] ?? '')),
            'ip' => trim((string) ($_POST['ip'] ?? '')),
            'panel_url' => trim((string) ($_POST['panel_url'] ?? '')),
            'login_hint' => trim((string) ($_POST['login_hint'] ?? '')),
            'secret_enc' => $secret !== '' ? encrypt_secret($secret) : null,
            'note_admin' => trim((string) ($_POST['note_admin'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'free'),
        ]);
        AdminLog::write((int) Auth::id(), 'server_create', client_ip());
        flash('success', 'Сервер добавлен.');
        redirect('/admin/servers');
    }

    public function update(string $id): void
    {
        Csrf::requireValid();
        $server = Server::findById((int) $id);
        if (!$server) {
            redirect('/admin/servers');
        }
        $secret = trim((string) ($_POST['secret'] ?? ''));
        $secretEnc = $server['secret_enc'];
        if ($secret !== '') {
            $secretEnc = encrypt_secret($secret);
        }
        Server::update((int) $id, [
            'product_id' => (int) ($_POST['product_id'] ?? 0) ?: null,
            'hostname' => trim((string) ($_POST['hostname'] ?? '')),
            'ip' => trim((string) ($_POST['ip'] ?? '')),
            'panel_url' => trim((string) ($_POST['panel_url'] ?? '')),
            'login_hint' => trim((string) ($_POST['login_hint'] ?? '')),
            'secret_enc' => $secretEnc,
            'note_admin' => trim((string) ($_POST['note_admin'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'free'),
        ]);
        AdminLog::write((int) Auth::id(), 'server_update', client_ip(), 'id=' . $id);
        flash('success', 'Сохранено.');
        redirect('/admin/servers');
    }

    public function delete(string $id): void
    {
        Csrf::requireValid();
        Server::delete((int) $id);
        flash('success', 'Удалено.');
        redirect('/admin/servers');
    }
}
