<?php
$used = (int)$used;
$limit = (int)$sub['domains_limit'];
?>
<p><a href="/account/proxy">← Белый вход</a></p>

<div class="grid-2">
  <div class="panel-card">
    <h2 style="font-size:1.05rem;margin-top:0"><?= e($sub['plan_title']) ?></h2>
    <p>Статус: <span class="pill"><?= e($sub['status']) ?></span><br>
      Доменов: <?= $used ?> / <?= $limit ?><br>
      Период до: <?= e($sub['period_end'] ?: '—') ?><br>
      IP входа: <strong><?= e($sub['node_ip'] ?: 'ещё не назначен админом') ?></strong></p>

    <?php if ($sub['node_ip']): ?>
      <div class="panel-card" style="background:#f0fdfa;border-color:#99f6e4;margin-top:0.8rem">
        <strong>Инструкция DNS</strong>
        <p style="margin:0.4rem 0 0;font-size:0.92rem">
          Поставьте A-записи <code>@</code> и <code>www</code> на
          <code><?= e($sub['node_ip']) ?></code>.<br>
          Cloudflare: только DNS (серое облако), без proxy.<br>
          После обновления DNS статус станет «активно» после проверки.
        </p>
      </div>
    <?php endif; ?>

    <?php if (in_array($sub['status'], ['active', 'suspended'], true)): ?>
      <form method="post" action="/account/proxy/<?= (int)$sub['id'] ?>/renew" style="margin-top:0.8rem">
        <?= \App\Core\Csrf::field() ?>
        <button class="btn btn-primary btn-sm" type="submit">Продлить на месяц — <?= money($sub['price_month']) ?></button>
      </form>
    <?php endif; ?>
  </div>

  <div class="panel-card form">
    <h2 style="font-size:1.05rem;margin-top:0">Добавить домен</h2>
    <?php if ($sub['status'] !== 'active'): ?>
      <p>Добавление доступно после активации подписки.</p>
    <?php elseif ($used >= $limit): ?>
      <p>Лимит доменов исчерпан.</p>
    <?php else: ?>
      <form method="post" action="/account/proxy/<?= (int)$sub['id'] ?>/domains">
        <?= \App\Core\Csrf::field() ?>
        <label>Домен (FQDN)</label>
        <input name="domain" placeholder="example.ru" required>
        <label>Origin host / IP</label>
        <input name="origin_host" placeholder="origin.example.ru или 1.2.3.4" required>
        <div class="row">
          <div><label>Порт</label><input name="origin_port" type="number" value="443"></div>
          <div><label style="margin-top:1.6rem"><input type="checkbox" name="origin_https" value="1" checked> Origin HTTPS</label></div>
        </div>
        <label>Origin SNI (опционально)</label>
        <input name="origin_sni" placeholder="если отличается от домена">
        <p style="margin-top:0.8rem"><button class="btn btn-primary" type="submit">Добавить</button></p>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="panel-card" style="margin-top:1rem">
  <h2 style="font-size:1.05rem;margin-top:0">Домены подписки</h2>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Домен</th><th>Origin</th><th>Статус</th><th>SSL</th><th>Проверка</th></tr></thead>
      <tbody>
      <?php foreach ($domains as $d): ?>
        <tr>
          <td><?= e($d['domain']) ?></td>
          <td><?= e($d['origin_host']) ?>:<?= (int)$d['origin_port'] ?></td>
          <td><?= e($d['status']) ?></td>
          <td><?= e($d['ssl_status']) ?></td>
          <td><?= $d['last_check_at'] ? e($d['last_check_at']) . ((int)$d['last_check_ok'] ? ' ✓' : ' ✗') : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$domains): ?><tr><td colspan="5">Пока пусто.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
