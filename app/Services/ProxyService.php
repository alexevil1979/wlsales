<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProxyDomain;
use App\Models\ProxyNode;
use App\Models\ProxySubscription;
use App\Models\Ticket;

final class ProxyService
{
    public static function checkDns(int $domainId): array
    {
        $domain = ProxyDomain::findById($domainId);
        if (!$domain) {
            throw new \RuntimeException('Домен не найден');
        }
        $expected = trim((string) ($domain['node_ip'] ?? ''));
        if ($expected === '') {
            ProxyDomain::setCheck($domainId, false, 'pending_dns');
            return ['ok' => false, 'ips' => [], 'message' => 'Нода ещё не назначена'];
        }

        $ips = ProxyDomain::resolveA((string) $domain['domain']);
        $ok = in_array($expected, $ips, true);
        ProxyDomain::setCheck($domainId, $ok, $ok ? 'active' : 'pending_dns');
        if ($domain['node_id']) {
            ProxyNode::recalculateUsed((int) $domain['node_id']);
        }
        return [
            'ok' => $ok,
            'ips' => $ips,
            'expected' => $expected,
            'message' => $ok
                ? 'A-запись совпадает с IP ноды'
                : 'A-запись не указывает на ' . $expected,
        ];
    }

    public static function nginxSnippet(array $domain): string
    {
        $tplPath = BASE_PATH . '/views/admin/proxy_nginx.txt';
        $tpl = is_file($tplPath)
            ? (string) file_get_contents($tplPath)
            : self::defaultSnippet();

        $scheme = !empty($domain['origin_https']) ? 'https' : 'http';
        $originHost = (string) $domain['origin_host'];
        $originPort = (int) ($domain['origin_port'] ?: 443);
        $serverName = (string) $domain['domain'];
        $sni = trim((string) ($domain['origin_sni'] ?? '')) ?: $serverName;

        return strtr($tpl, [
            '{domain}' => $serverName,
            '{origin_host}' => $originHost,
            '{origin_port}' => (string) $originPort,
            '{origin_scheme}' => $scheme,
            '{origin_sni}' => $sni,
        ]);
    }

    public static function markNodeDead(int $nodeId): int
    {
        ProxyNode::markDead($nodeId);
        $n = ProxyDomain::markErrorForNode($nodeId);
        $subs = ProxySubscription::forNode($nodeId);
        foreach ($subs as $sub) {
            Ticket::create(
                (int) $sub['user_id'],
                'Белый вход: нода недоступна — нужна замена входа',
                null
            );
        }
        return $n;
    }

    public static function processRenewals(): array
    {
        $reminded = 0;
        foreach (ProxySubscription::expiringInDays(3) as $sub) {
            Mailer::send(
                (string) $sub['email'],
                'Продление белого входа через 3 дня',
                '<p>Подписка «' . e((string) $sub['plan_title']) . '» заканчивается '
                . e((string) $sub['period_end']) . '.</p>'
                . '<p>Продлите в кабинете: ' . e(app_url('/account/proxy')) . '</p>'
            );
            $reminded++;
        }

        $suspended = 0;
        foreach (ProxySubscription::dueToday() as $sub) {
            ProxySubscription::setStatus((int) $sub['id'], 'suspended');
            Mailer::send(
                (string) $sub['email'],
                'Белый вход приостановлен',
                '<p>Срок подписки истёк (' . e((string) $sub['period_end']) . '). '
                . 'Доступ приостановлен до оплаты продления.</p>'
            );
            $suspended++;
        }

        return compact('reminded', 'suspended');
    }

    private static function defaultSnippet(): string
    {
        return <<<'NGINX'
# HTTP → HTTPS
server {
  listen 80;
  server_name {domain};
  return 301 https://$host$request_uri;
}

server {
  listen 443 ssl http2;
  server_name {domain};

  # ssl_certificate     /etc/letsencrypt/live/{domain}/fullchain.pem;
  # ssl_certificate_key /etc/letsencrypt/live/{domain}/privkey.pem;

  location / {
    proxy_pass {origin_scheme}://{origin_host}:{origin_port};
    proxy_set_header Host {domain};
    proxy_ssl_server_name on;
    proxy_ssl_name {origin_sni};
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto https;
  }
}
NGINX;
    }
}
