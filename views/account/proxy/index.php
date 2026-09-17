<?php
$proxyStatus = static function (string $s): string {
    return match ($s) {
        'new' => 'Новая',
        'awaiting_payment' => 'Ожидает оплаты',
        'active' => 'Активна',
        'suspended' => 'Приостановлена',
        'cancelled' => 'Отменена',
        default => $s,
    };
};
$domStatus = static function (string $s): string {
    return match ($s) {
        'pending_dns' => 'Ждём DNS',
        'active' => 'Активно',
        'error' => 'Ошибка',
        'disabled' => 'Отключено',
        default => $s,
    };
};
?>
<div class="panel-card" style="margin-bottom:1rem">
  <p style="margin:0">Подписки на вход с проверенного публичного IP. После оплаты и назначения ноды поставьте A-запись на выданный адрес (без оранжевого Cloudflare).</p>
  <p style="margin:0.6rem 0 0"><a class="btn btn-primary btn-sm" href="/proxy">Смотреть тарифы входа</a></p>
</div>

<?php if (!empty($missingSchema)): ?>
<div class="panel-card"><p class="flash flash-error" style="margin:0">Таблицы белого входа ещё не созданы. Админу: выполните <code>sql/002_proxy.sql</code> на VPS.</p></div>
<?php else: ?>

<div class="panel-card" style="margin-bottom:1rem">
  <h2 style="font-size:1.05rem;margin-top:0">Подписки</h2>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>ID</th><th>Тариф</th><th>IP</th><th>До</th><th>Статус</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($subscriptions as $s): ?>
        <tr>
          <td>#<?= (int)$s['id'] ?></td>
          <td><?= e($s['plan_title']) ?> (<?= (int)$s['domains_limit'] ?> дом.)</td>
          <td><?= e($s['node_ip'] ?: 'ожидает назначения') ?></td>
          <td><?= e($s['period_end'] ?: '—') ?></td>
          <td><span class="pill"><?= e($proxyStatus($s['status'])) ?></span></td>
          <td><a href="/account/proxy/<?= (int)$s['id'] ?>">Открыть</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$subscriptions): ?><tr><td colspan="6">Пока нет подписок. <a href="/proxy">Подключить</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel-card">
  <h2 style="font-size:1.05rem;margin-top:0">Домены</h2>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Домен</th><th>Origin</th><th>IP входа</th><th>Статус</th><th>SSL</th></tr></thead>
      <tbody>
      <?php foreach ($domains as $d): ?>
        <tr>
          <td><?= e($d['domain']) ?></td>
          <td><?= e($d['origin_host']) ?>:<?= (int)$d['origin_port'] ?></td>
          <td><?= e($d['node_ip'] ?: '—') ?></td>
          <td><?= e($domStatus($d['status'])) ?></td>
          <td><?= e($d['ssl_status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$domains): ?><tr><td colspan="5">Доменов пока нет.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel-card form" style="margin-top:1rem">
  <h2 style="font-size:1.05rem;margin-top:0">Тикет по белому входу</h2>
  <form method="post" action="/account/proxy/ticket">
    <?= \App\Core\Csrf::field() ?>
    <label>Тема</label>
    <select name="subject">
      <option value="Белый вход: не открывается">Не открывается</option>
      <option value="Белый вход: сменить origin">Сменить origin</option>
      <option value="Белый вход: другое">Другое</option>
    </select>
    <label>Сообщение</label>
    <textarea name="body" rows="3" required></textarea>
    <p style="margin-top:0.8rem"><button class="btn btn-primary btn-sm" type="submit">Создать тикет</button></p>
  </form>
</div>
<?php endif; ?>
