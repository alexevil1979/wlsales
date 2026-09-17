<div class="fi-header">
  <div>
    <h2 class="fi-header-heading" style="font-size:1.25rem;margin:0">Платежи</h2>
    <p class="fi-header-sub">Подтверждение ручных и список всех платежей</p>
  </div>
  <div class="fi-header-actions">
    <a class="btn btn-ghost" href="/admin/payments/gateways">Платёжные шлюзы</a>
  </div>
</div>

<?php if ($waiting): ?>
  <div class="panel-card" style="margin-bottom:1.25rem">
    <h2 class="fi-section-title">Ожидают подтверждения</h2>
    <div class="table-wrap">
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
  </div>
<?php endif; ?>

<div class="table-wrap">
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
        <td><span class="pill"><?= e($p['status']) ?></span></td>
        <td><?= e($p['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
