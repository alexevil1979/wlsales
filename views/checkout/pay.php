<section class="section container page-narrow">
  <h1>Оплата заказа #<?= (int)$order['id'] ?></h1>
  <div class="card-soft" style="margin-bottom:1rem">
    <p><?= e($order['product_title']) ?><br>
      Тариф: <?= e(tariff_label($order['tariff'])) ?><br>
      К оплате: <strong style="font-size:1.3rem;color:var(--accent)"><?= money($order['amount']) ?></strong></p>
  </div>

  <?php if ($order['status'] !== 'awaiting_payment'): ?>
    <p class="flash flash-success">Заказ уже не ожидает оплаты. <a href="/account/orders/<?= (int)$order['id'] ?>">К заказу</a></p>
  <?php else: ?>
    <?php if (!$gateways): ?>
      <p class="flash flash-error">Способы оплаты временно недоступны. Напишите в <a href="/contacts">поддержку</a>.</p>
    <?php else: ?>
      <div class="grid-2">
        <?php foreach ($gateways as $gw): ?>
          <div class="card-soft">
            <h2 style="font-size:1.05rem">
              <?php
                echo match ($gw->name()) {
                    'manual_sbp' => 'СБП / перевод',
                    'yookassa' => 'ЮKassa (карта)',
                    'crypto_usdt' => 'USDT TRC20',
                    default => $gw->name(),
                };
              ?>
            </h2>
            <?php if ($gw->name() === 'manual_sbp'): ?>
              <p>Телефон СБП: <strong><?= e(setting('sbp_phone')) ?></strong></p>
              <p style="font-size:0.9rem"><?= e(str_replace('{order_id}', (string)$order['id'], setting('sbp_comment', 'Оплата заказа #{order_id}'))) ?></p>
            <?php elseif ($gw->name() === 'crypto_usdt'): ?>
              <p>USDT TRC20:</p>
              <p><code style="word-break:break-all"><?= e(setting('crypto_usdt_trc20')) ?></code></p>
              <p style="font-size:0.85rem">Сумма в рублях по курсу на момент оплаты — согласуйте в тикете при необходимости.</p>
            <?php else: ?>
              <p>Оплата картой через ЮKassa. Вы будете перенаправлены на страницу банка.</p>
            <?php endif; ?>
            <form method="post" action="/pay/<?= (int)$order['id'] ?>">
              <?= \App\Core\Csrf::field() ?>
              <input type="hidden" name="provider" value="<?= e($gw->name()) ?>">
              <button class="btn btn-primary btn-sm" type="submit">Выбрать</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>

      <?php
        $last = $payments[0] ?? null;
        if ($last && in_array($last['provider'], ['manual_sbp', 'crypto_usdt'], true) && $last['status'] === 'pending'):
      ?>
        <div class="card-soft" style="margin-top:1rem">
          <h2 style="font-size:1.05rem">Подтверждение перевода</h2>
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
