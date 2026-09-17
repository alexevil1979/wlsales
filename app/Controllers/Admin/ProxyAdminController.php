<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\ProxyDomain;
use App\Models\ProxyNode;
use App\Models\ProxyPlan;
use App\Models\ProxySubscription;
use App\Models\Server;
use App\Services\ProxyService;

final class ProxyAdminController
{
    public function index(): void
    {
        if (!\App\Core\Database::hasTable('proxy_nodes')) {
            View::render('admin/proxy/missing', [
                'title' => 'Белый вход',
                'breadcrumbs' => [
                    ['label' => 'Белый вход'],
                ],
            ], 'admin');
            return;
        }
        View::render('admin/proxy/index', [
            'title' => 'Белый вход',
            'nodes' => ProxyNode::all(),
            'subscriptions' => ProxySubscription::allAdmin(),
            'dnsQueue' => ProxyDomain::queueDns(),
            'plans' => ProxyPlan::all(),
            'servers' => Server::allAdmin(),
        ], 'admin');
    }

    public function createNode(): void
    {
        Csrf::requireValid();
        $serverId = (int) ($_POST['server_id'] ?? 0) ?: null;
        $ip = trim((string) ($_POST['public_ip'] ?? ''));
        $hostname = trim((string) ($_POST['hostname'] ?? ''));
        if ($serverId) {
            $server = Server::findById($serverId);
            if ($server) {
                if ($ip === '') {
                    $ip = (string) $server['ip'];
                }
                if ($hostname === '') {
                    $hostname = (string) $server['hostname'];
                }
                if (ProxyNode::findByServerId($serverId)) {
                    flash('error', 'Этот сервер уже привязан к ноде.');
                    redirect('/admin/proxy');
                }
            }
        }
        if ($ip === '') {
            flash('error', 'Укажите public IP.');
            redirect('/admin/proxy');
        }
        ProxyNode::create([
            'server_id' => $serverId,
            'hostname' => $hostname,
            'public_ip' => $ip,
            'location' => trim((string) ($_POST['location'] ?? '')),
            'vendor' => trim((string) ($_POST['vendor'] ?? '')),
            'domains_cap' => (int) ($_POST['domains_cap'] ?? 50),
            'status' => (string) ($_POST['status'] ?? 'active'),
            'note_admin' => trim((string) ($_POST['note_admin'] ?? '')),
        ]);
        AdminLog::write((int) Auth::id(), 'proxy_node_create', client_ip(), $ip);
        flash('success', 'Нода создана.');
        redirect('/admin/proxy');
    }

    public function updateNode(string $id): void
    {
        Csrf::requireValid();
        ProxyNode::update((int) $id, [
            'server_id' => (int) ($_POST['server_id'] ?? 0) ?: null,
            'hostname' => trim((string) ($_POST['hostname'] ?? '')),
            'public_ip' => trim((string) ($_POST['public_ip'] ?? '')),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'vendor' => trim((string) ($_POST['vendor'] ?? '')),
            'domains_cap' => (int) ($_POST['domains_cap'] ?? 50),
            'status' => (string) ($_POST['status'] ?? 'active'),
            'note_admin' => trim((string) ($_POST['note_admin'] ?? '')),
        ]);
        ProxyNode::recalculateUsed((int) $id);
        flash('success', 'Нода обновлена.');
        redirect('/admin/proxy');
    }

    public function markNodeDead(string $id): void
    {
        Csrf::requireValid();
        $n = ProxyService::markNodeDead((int) $id);
        AdminLog::write((int) Auth::id(), 'proxy_node_dead', client_ip(), 'node=' . $id . ';domains=' . $n);
        flash('success', "Нода помечена dead, доменов в error: {$n}, тикеты созданы.");
        redirect('/admin/proxy');
    }

    public function assignNode(string $id): void
    {
        Csrf::requireValid();
        $sub = ProxySubscription::findById((int) $id);
        if (!$sub) {
            redirect('/admin/proxy');
        }
        $nodeId = (int) ($_POST['node_id'] ?? 0);
        $node = ProxyNode::findById($nodeId);
        if (!$node) {
            flash('error', 'Нода не найдена.');
            redirect('/admin/proxy');
        }
        if ((int) $sub['dedicated_ip'] === 1 && (int) $node['domains_used'] > 0) {
            flash('error', 'Для выделенного входа нужна свободная нода (0 доменов).');
            redirect('/admin/proxy');
        }
        ProxySubscription::assignNode((int) $id, $nodeId);
        ProxyNode::recalculateUsed($nodeId);
        if (!empty($sub['node_id']) && (int) $sub['node_id'] !== $nodeId) {
            ProxyNode::recalculateUsed((int) $sub['node_id']);
        }
        flash('success', 'Нода назначена подписке.');
        redirect('/admin/proxy');
    }

    public function checkDns(string $id): void
    {
        Csrf::requireValid();
        try {
            $result = ProxyService::checkDns((int) $id);
            flash($result['ok'] ? 'success' : 'error', $result['message'] . (isset($result['ips']) ? ' [' . implode(', ', $result['ips']) . ']' : ''));
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/proxy');
    }

    public function updateDomain(string $id): void
    {
        Csrf::requireValid();
        $domain = ProxyDomain::findById((int) $id);
        if (!$domain) {
            redirect('/admin/proxy');
        }
        ProxyDomain::updateAdmin((int) $id, [
            'origin_host' => trim((string) ($_POST['origin_host'] ?? $domain['origin_host'])),
            'origin_port' => (int) ($_POST['origin_port'] ?? $domain['origin_port']),
            'origin_https' => isset($_POST['origin_https']) ? 1 : 0,
            'origin_sni' => trim((string) ($_POST['origin_sni'] ?? '')),
            'ssl_status' => (string) ($_POST['ssl_status'] ?? $domain['ssl_status']),
            'status' => (string) ($_POST['status'] ?? $domain['status']),
            'note_admin' => trim((string) ($_POST['note_admin'] ?? '')),
        ]);
        if (!empty($domain['node_id'])) {
            ProxyNode::recalculateUsed((int) $domain['node_id']);
        }
        flash('success', 'Домен обновлён.');
        redirect('/admin/proxy#domain-' . $id);
    }

    public function snippet(string $id): void
    {
        $domain = ProxyDomain::findById((int) $id);
        if (!$domain) {
            flash('error', 'Домен не найден.');
            redirect('/admin/proxy');
        }
        View::render('admin/proxy/snippet', [
            'title' => 'Nginx сниппет — ' . $domain['domain'],
            'domain' => $domain,
            'snippet' => ProxyService::nginxSnippet($domain),
        ], 'admin');
    }

    public function makeNodeFromServer(string $id): void
    {
        Csrf::requireValid();
        $server = Server::findById((int) $id);
        if (!$server) {
            flash('error', 'Сервер не найден.');
            redirect('/admin/servers');
        }
        if (ProxyNode::findByServerId((int) $id)) {
            flash('error', 'Уже есть нода для этого сервера.');
            redirect('/admin/servers');
        }
        if (($server['status'] ?? '') === 'assigned' && ProxyDomain::allAdmin()) {
            // soft warning only if domains exist on any node of this IP later
        }
        ProxyNode::create([
            'server_id' => (int) $id,
            'hostname' => (string) $server['hostname'],
            'public_ip' => (string) $server['ip'],
            'location' => '',
            'vendor' => '',
            'domains_cap' => (int) ($_POST['domains_cap'] ?? 50),
            'status' => 'active',
            'note_admin' => 'Создано из инвентаря servers#' . $id,
        ]);
        flash('success', 'Сервер добавлен как proxy-нода.');
        redirect('/admin/proxy');
    }
}
