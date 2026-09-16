<section class="section container page-narrow">
  <p><a href="/account/orders">← Заказы</a></p>
  <h1>Заказ #<?= (int)$order['id'] ?></h1>
  <div class="card-soft">
    <p><strong><?= e($order['product_title']) ?></strong><br>
      <?= e($order['vendor']) ?> · <?= e($order['location']) ?></p>
    <p>Тариф: <?= e(tariff_label($order['tariff'])) ?><br>
      Сумма: <strong><?= money($order['amount']) ?></strong><br>
      Статус: <span class="pill"><?= e(order_status_label($order['status'])) ?></span></p>
    <?php if ($order['status'] === 'awaiting_payment'): ?>
      <p><a class="btn btn-primary" href="/pay/<?= (int)$order['id'] ?>">Перейти к оплате</a></p>
    <?php elseif (in_array($order['status'], ['paid', 'pending_stock'], true)): ?>
      <p class="flash flash-success" style="margin:0">Оплата получена. Ожидайте выдачи доступов в кабинете.</p>
    <?php endif; ?>
  </div>
  <?php if ($server): ?>
    <div class="card-soft" style="margin-top:1rem">
      <h2 style="font-size:1.1rem">Выданные доступы</h2>
      <table class="specs">
        <tr><th>IP</th><td><?= e($server['ip']) ?></td></tr>
        <tr><th>Hostname</th><td><?= e($server['hostname']) ?></td></tr>
        <tr><th>Панель</th><td><?php if ($server['panel_url']): ?><a href="<?= e($server['panel_url']) ?>" target="_blank" rel="noopener"><?= e($server['panel_url']) ?></a><?php else: ?>—<?php endif; ?></td></tr>
        <tr><th>Логин</th><td><?= e($server['login_hint'] ?: 'root / см. панель') ?></td></tr>
      </table>
      <p><a href="/account/servers">Все серверы</a></p>
    </div>
  <?php endif; ?>
</section>
