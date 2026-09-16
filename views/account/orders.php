<section class="section container">
  <h1>Мои заказы</h1>
  <div class="table-wrap card-soft">
    <table class="data">
      <thead><tr><th>ID</th><th>Лот</th><th>Тариф</th><th>Сумма</th><th>Статус</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td>#<?= (int)$o['id'] ?></td>
          <td><?= e($o['product_title']) ?></td>
          <td><?= e(tariff_label($o['tariff'])) ?></td>
          <td><?= money($o['amount']) ?></td>
          <td><?= e(order_status_label($o['status'])) ?></td>
          <td><a href="/account/orders/<?= (int)$o['id'] ?>">Открыть</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?><tr><td colspan="6">Заказов пока нет. <a href="/catalog">В каталог</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
