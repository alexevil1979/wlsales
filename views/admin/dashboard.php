<div class="panel-stats">
  <div class="panel-stat"><div class="n"><?= (int)$newOrders ?></div><div class="l">Новые / ждут оплату</div></div>
  <div class="panel-stat"><div class="n"><?= (int)$awaitingPayment ?></div><div class="l">Ожидают оплату</div></div>
  <div class="panel-stat"><div class="n"><?= (int)$awaitingDelivery ?></div><div class="l">Ждут выдачи</div></div>
  <div class="panel-stat"><div class="n"><?= money($revenue30) ?></div><div class="l">Выручка 30 дней</div></div>
</div>

<div class="panel-stats" style="grid-template-columns:repeat(2,minmax(0,1fr));margin-top:-0.25rem">
  <div class="panel-stat"><div class="n" style="font-size:1.35rem"><?= (int)$users ?></div><div class="l">Пользователей</div></div>
  <div class="panel-stat"><div class="n" style="font-size:1.35rem"><?= (int)$openTickets ?></div><div class="l">Открытых тикетов</div></div>
</div>

<?php if ($waitingPayments): ?>
  <div class="panel-card" style="margin-bottom:1rem">
    <h2 class="fi-section-title">Ждут подтверждения оплаты</h2>
    <p class="fi-section-desc">Ручные СБП / USDT — подтвердите после проверки поступления.</p>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Платёж</th><th>Заказ</th><th>Email</th><th>Сумма</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($waitingPayments as $p): ?>
          <tr>
            <td>#<?= (int)$p['id'] ?> · <?= e(\App\Services\PaymentService::label((string)$p['provider'])) ?></td>
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
  <h2 class="fi-section-title">Последние действия админов</h2>
  <p class="fi-section-desc">Журнал входов и изменений.</p>
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
