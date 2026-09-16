<?php if ($waiting): ?>
  <h2 style="font-size:1.1rem">Ожидают подтверждения</h2>
  <div class="table-wrap card-soft" style="margin-bottom:1.2rem">
    <table class="data">
      <thead><tr><th>ID</th><th>Заказ</th><th>Email</th><th>Провайдер</th><th>Сумма</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($waiting as $p): ?>
        <tr>
          <td>#<?= (int)$p['id'] ?></td>
          <td><a href="/admin/orders/<?= (int)$p['order_id'] ?>">#<?= (int)$p['order_id'] ?></a></td>
          <td><?= e($p['email']) ?></td>
          <td><?= e($p['provider']) ?></td>
          <td><?= money($p['amount']) ?></td>
          <td>
            <form method="post" action="/admin/payments/<?= (int)$p['id'] ?>/confirm">
              <?= \App\Core\Csrf::field() ?>
              <button class="btn btn-primary btn-sm" type="submit">Подтвердить</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<div class="table-wrap card-soft">
  <table class="data">
    <thead><tr><th>ID</th><th>Заказ</th><th>Клиент</th><th>Провайдер</th><th>External</th><th>Сумма</th><th>Статус</th><th>Дата</th></tr></thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
      <tr>
        <td>#<?= (int)$p['id'] ?></td>
        <td>#<?= (int)$p['order_id'] ?></td>
        <td><?= e($p['user_email']) ?></td>
        <td><?= e($p['provider']) ?></td>
        <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis"><?= e($p['external_id']) ?></td>
        <td><?= money($p['amount']) ?></td>
        <td><?= e($p['status']) ?></td>
        <td><?= e($p['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
