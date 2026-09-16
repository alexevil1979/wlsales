<p><a href="/admin/orders">← Заказы</a></p>
<div class="grid-2" style="align-items:start">
  <div class="card-soft">
    <p>Клиент: <?= e($order['user_email']) ?> (<?= e($order['user_name']) ?>)</p>
    <p>Лот: <?= e($order['product_title']) ?> · <?= e($order['vendor']) ?> / <?= e($order['location']) ?></p>
    <p>Тариф: <?= e(tariff_label($order['tariff'])) ?><br>
      Сумма: <strong><?= money($order['amount']) ?></strong><br>
      Провайдер: <?= e($order['payment_provider'] ?: '—') ?><br>
      Статус: <?= e(order_status_label($order['status'])) ?></p>
    <p style="white-space:pre-wrap;color:var(--muted)"><?= e((string)$order['admin_comment']) ?></p>
  </div>
  <div>
    <form method="post" action="/admin/orders/<?= (int)$order['id'] ?>/status" class="form card-soft" style="margin-bottom:1rem">
      <?= \App\Core\Csrf::field() ?>
      <label>Статус</label>
      <select name="status">
        <?php foreach (['new','awaiting_payment','paid','pending_stock','delivered','cancelled','refund'] as $st): ?>
          <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= e(order_status_label($st)) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Комментарий админа</label>
      <textarea name="admin_comment" rows="3"><?= e((string)$order['admin_comment']) ?></textarea>
      <p style="margin-top:0.8rem"><button class="btn btn-primary btn-sm" type="submit">Сохранить</button></p>
    </form>
    <form method="post" action="/admin/orders/<?= (int)$order['id'] ?>/deliver" class="form card-soft">
      <?= \App\Core\Csrf::field() ?>
      <h3 style="font-size:1rem">Выдать доступы</h3>
      <label>Сервер из инвентаря</label>
      <select name="server_id" required>
        <option value="">— выберите —</option>
        <?php foreach ($servers as $s): ?>
          <option value="<?= (int)$s['id'] ?>">#<?= (int)$s['id'] ?> <?= e($s['ip']) ?> <?= e($s['hostname']) ?></option>
        <?php endforeach; ?>
      </select>
      <p style="font-size:0.8rem;color:var(--muted)">Показаны свободные серверы (free) по лоту или без привязки.</p>
      <p style="margin-top:0.8rem"><button class="btn btn-primary" type="submit">Выдать клиенту</button></p>
    </form>
  </div>
</div>
