<section class="page-narrow" style="max-width:820px">
  <div class="panel-card" style="margin-bottom:1rem">
    <p style="margin:0"><?= e($order['product_title']) ?><br>
      Тариф: <?= e(tariff_label($order['tariff'])) ?><br>
      К оплате: <strong style="font-size:1.35rem;color:var(--panel-primary,#14b8a6)"><?= money($order['amount']) ?></strong></p>
  </div>

  <?php if ($order['status'] !== 'awaiting_payment'): ?>
    <p class="flash flash-success">Заказ уже не ожидает оплаты. <a href="/account/orders/<?= (int)$order['id'] ?>">К заказу</a></p>
  <?php else: ?>
    <?php if (!$gateways): ?>
      <p class="flash flash-error">Способы оплаты временно недоступны. Напишите в <a href="/contacts">поддержку</a>.</p>
    <?php else: ?>
      <div class="grid-2">
        <?php foreach ($gateways as $gw): ?>
          <?php $code = $gw->name(); ?>
          <div class="panel-card">
            <h2 style="font-size:1.05rem;margin-top:0"><?= e(\App\Services\PaymentService::label($code)) ?></h2>
            <?php if ($code === 'manual_sbp'): ?>
              <?php
                $sbp = $gw instanceof \App\Services\ManualSbpGateway ? $gw : new \App\Services\ManualSbpGateway();
              ?>
              <p>Телефон СБП: <strong><?= e($sbp->phone()) ?></strong></p>
              <p style="font-size:0.9rem"><?= e(str_replace('{order_id}', (string)$order['id'], $sbp->comment())) ?></p>
            <?php elseif ($code === 'crypto_usdt'): ?>
              <?php
                $crypto = $gw instanceof \App\Services\CryptoStubGateway ? $gw : new \App\Services\CryptoStubGateway();
              ?>
              <p>USDT TRC20:</p>
              <p><code style="word-break:break-all"><?= e($crypto->address()) ?></code></p>
            <?php elseif (str_starts_with($code, 'freekassa')): ?>
              <p>Оплата через FreeKassa. После выбора откроется страница провайдера.</p>
            <?php elseif ($code === 'nowpayments'): ?>
              <p>Криптооплата (NOWPayments). Будете перенаправлены на invoice.</p>
            <?php elseif ($code === 'platega'): ?>
              <p>Оплата через Platega.</p>
            <?php elseif ($code === 'yookassa'): ?>
              <p>Карта / СБП через ЮKassa.</p>
            <?php else: ?>
              <p>Оплата через <?= e(\App\Services\PaymentService::label($code)) ?>.</p>
            <?php endif; ?>
            <form method="post" action="/pay/<?= (int)$order['id'] ?>">
              <?= \App\Core\Csrf::field() ?>
              <input type="hidden" name="provider" value="<?= e($code) ?>">
              <button class="btn btn-primary btn-sm" type="submit">Выбрать</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>

      <?php
        $last = $payments[0] ?? null;
        if ($last && in_array($last['provider'], ['manual_sbp', 'crypto_usdt'], true) && $last['status'] === 'pending'):
      ?>
        <div class="panel-card" style="margin-top:1rem">
          <h2 style="font-size:1.05rem;margin-top:0">Подтверждение перевода</h2>
          <p>После оплаты нажмите кнопку — администратор проверит поступление.</p>
          <form method="post" action="/pay/<?= (int)$order['id'] ?>/confirm">
            <?= \App\Core\Csrf::field() ?>
            <button class="btn btn-primary" type="submit">Я оплатил</button>
          </form>
        </div>
      <?php elseif ($last && $last['status'] === 'waiting_confirm'): ?>
        <p class="flash flash-success" style="margin-top:1rem">Ожидаем подтверждения администратора.</p>
      <?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>
</section>
