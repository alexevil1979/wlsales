<div class="panel-stats">
  <div class="panel-stat"><div class="n"><?= (int)$newOrders ?></div><div class="l">Новые / ждут оплату</div></div>
  <div class="panel-stat"><div class="n"><?= (int)$awaitingPayment ?></div><div class="l">Ожидают оплату</div></div>
  <div class="panel-stat"><div class="n"><?= (int)$awaitingDelivery ?></div><div class="l">Ждут выдачи</div></div>
  <div class="panel-stat"><div class="n"><?= money($revenue30) ?></div><div class="l">Выручка 30 дней</div></div>
</div>
<p>Пользователей: <?= (int)$users ?> · Открытых тикетов: <?= (int)$openTickets ?></p>

<?php if ($waitingPayments): ?>
  <div class="panel-card" style="margin-bottom:1rem">
    <h2 style="font-size:1.05rem;margin-top:0">Ждут подтверждения оплаты</h2>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Платёж</th><th>Заказ</th><th>Email</th><th>Сумма</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($waitingPayments as $p): ?>
          <tr>
            <td>#<?= (int)$p['id'] ?> <?= e(\App\Services\PaymentService::label((string)$p['provider'])) ?></td>
            <td><a href="/admin/orders/<?= (int)$p['order_id'] ?>">#<?= (int)$p['order_id'] ?></a></td>
            <td><?= e($p['email']) ?></td>
            <td><?= money($p['amount']) ?></td>
            <td>
              <form method="post" action="/admin/payments/<?= (int)$p['id'] ?>/confirm" style="display:inline">
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

<div class="panel-card">
  <h2 style="font-size:1.05rem;margin-top:0">Последние входы админов</h2>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Когда</th><th>Email</th><th>Действие</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td><?= e($l['created_at']) ?></td>
          <td><?= e($l['email']) ?></td>
          <td><?= e($l['action']) ?></td>
          <td><?= e($l['ip']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
