<section class="section container">
  <h1>Мои серверы</h1>
  <?php if (!$servers): ?>
    <p class="card-soft">Пока нет выданных серверов. После оплаты и выдачи доступов они появятся здесь.</p>
  <?php endif; ?>
  <div class="grid-2">
    <?php foreach ($servers as $s): ?>
      <article class="card-soft">
        <h2 style="font-size:1.1rem"><?= e($s['product_title'] ?? 'Сервер') ?></h2>
        <p class="meta"><?= e($s['vendor'] ?? '') ?> · <?= e($s['location'] ?? '') ?></p>
        <table class="specs">
          <tr><th>IP</th><td><code><?= e($s['ip']) ?></code></td></tr>
          <tr><th>Hostname</th><td><?= e($s['hostname']) ?></td></tr>
          <tr><th>Панель</th><td><?php if ($s['panel_url']): ?><a href="<?= e($s['panel_url']) ?>" target="_blank" rel="noopener">Открыть</a><?php else: ?>—<?php endif; ?></td></tr>
          <tr><th>Логин</th><td><?= e($s['login_hint'] ?: '—') ?></td></tr>
          <tr>
            <th>Секрет</th>
            <td>
              <?php
                $plain = decrypt_secret($s['secret_enc'] ?? null);
                $sid = 'sec' . (int)$s['id'];
              ?>
              <?php if ($plain !== ''): ?>
                <span id="<?= e($sid) ?>" data-secret="<?= e($plain) ?>">••••••••</span>
                <button type="button" class="btn btn-ghost btn-sm" data-reveal-secret="<?= e($sid) ?>">Показать</button>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        </table>
        <?php if (!empty($s['assigned_order_id'])): ?>
          <p><a href="/account/orders/<?= (int)$s['assigned_order_id'] ?>">Заказ #<?= (int)$s['assigned_order_id'] ?></a></p>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
  <p style="margin-top:1rem"><a href="/account/tickets">Проблема с IP? Создайте тикет</a></p>
</section>
