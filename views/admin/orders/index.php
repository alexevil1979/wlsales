<form class="filters" method="get">
  <select name="status">
    <option value="">Все статусы</option>
    <?php foreach (['awaiting_payment','paid','pending_stock','delivered','cancelled','refund'] as $st): ?>
      <option value="<?= $st ?>" <?= ($filterStatus ?? '') === $st ? 'selected' : '' ?>><?= e(order_status_label($st)) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-ghost btn-sm" type="submit">Фильтр</button>
</form>
<div class="table-wrap card-soft">
  <table class="data">
    <thead><tr><th>ID</th><th>Клиент</th><th>Лот</th><th>Тариф</th><th>Сумма</th><th>Статус</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= (int)$o['id'] ?></td>
        <td><?= e($o['user_email']) ?></td>
        <td><?= e($o['product_title']) ?></td>
        <td><?= e(tariff_label($o['tariff'])) ?></td>
        <td><?= money($o['amount']) ?></td>
        <td><?= e(order_status_label($o['status'])) ?></td>
        <td><a href="/admin/orders/<?= (int)$o['id'] ?>">Открыть</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
