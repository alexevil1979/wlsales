<?php
/** @var array $settings */
/** @var array $enabled */
$s = $settings;
$mask = static function (string $key) use ($s): string {
    $v = trim((string) ($s[$key] ?? ''));
    return $v !== '' ? '********' : '';
};
$flag = static function (string $key, bool $defaultOn = true) use ($s): bool {
    if (!array_key_exists($key, $s)) {
        return $defaultOn;
    }
    return ($s[$key] ?? '0') === '1';
};
?>
<p style="margin-top:0">Ключи можно задать здесь <strong>или</strong> в <code>.env</code>. Значение из админки имеет приоритет. Секреты при сохранении не затираются, если оставить поле пустым / <code>********</code>.</p>

<form method="post" action="/admin/payments/gateways" class="form">
  <?= \App\Core\Csrf::field() ?>

  <div class="panel-card" style="margin-bottom:1rem">
    <h2 style="font-size:1.05rem;margin-top:0">
      СБП вручную
      <span class="pill"><?= !empty($enabled['manual_sbp']) ? 'вкл' : 'выкл' ?></span>
    </h2>
    <label><input type="checkbox" name="pay_manual_sbp_on" value="1" <?= $flag('pay_manual_sbp_on', true) ? 'checked' : '' ?>> Показывать на оплате</label>
    <div class="row">
      <div><label>Телефон СБП</label><input name="sbp_phone" value="<?= e($s['sbp_phone'] ?? '') ?>"></div>
      <div><label>Комментарий к переводу</label><input name="sbp_comment" value="<?= e($s['sbp_comment'] ?? 'Оплата заказа #{order_id}') ?>"></div>
    </div>
  </div>

  <div class="panel-card" style="margin-bottom:1rem">
    <h2 style="font-size:1.05rem;margin-top:0">
      USDT TRC20 вручную
      <span class="pill"><?= !empty($enabled['crypto_usdt']) ? 'вкл' : 'выкл' ?></span>
    </h2>
    <label><input type="checkbox" name="pay_crypto_on" value="1" <?= $flag('pay_crypto_on', true) ? 'checked' : '' ?>> Показывать (если адрес не заглушка)</label>
    <label>Адрес кошелька</label>
    <input name="crypto_usdt_trc20" value="<?= e($s['crypto_usdt_trc20'] ?? '') ?>">
  </div>

  <div class="panel-card" style="margin-bottom:1rem">
    <h2 style="font-size:1.05rem;margin-top:0">
      ЮKassa
      <span class="pill"><?= !empty($enabled['yookassa']) ? 'вкл' : 'выкл' ?></span>
    </h2>
    <label><input type="checkbox" name="pay_yookassa_on" value="1" <?= $flag('pay_yookassa_on', true) ? 'checked' : '' ?>> Включить</label>
    <div class="row">
      <div><label>shopId</label><input name="pay_yookassa_shop_id" value="<?= e($s['pay_yookassa_shop_id'] ?? '') ?>" autocomplete="off"></div>
      <div><label>secretKey</label><input name="pay_yookassa_secret_key" value="<?= e($mask('pay_yookassa_secret_key')) ?>" autocomplete="new-password"></div>
    </div>
    <label>Return URL</label>
    <input name="pay_yookassa_return_url" value="<?= e($s['pay_yookassa_return_url'] ?? app_url('/account/orders')) ?>">
    <p style="font-size:0.85rem;margin:0.5rem 0 0">Webhook: <code><?= e(app_url('/webhooks/yookassa')) ?></code></p>
  </div>

  <div class="panel-card" style="margin-bottom:1rem">
    <h2 style="font-size:1.05rem;margin-top:0">
      FreeKassa (СБП / карты РФ / world USD)
      <span class="pill"><?= !empty($enabled['freekassa_sbp']) ? 'вкл' : 'выкл' ?></span>
    </h2>
    <label><input type="checkbox" name="pay_freekassa_on" value="1" <?= $flag('pay_freekassa_on', true) ? 'checked' : '' ?>> Включить (три метода на витрине)</label>
    <div class="row">
      <div><label>shopId</label><input name="pay_freekassa_shop_id" value="<?= e($s['pay_freekassa_shop_id'] ?? '') ?>"></div>
      <div><label>API key</label><input name="pay_freekassa_api_key" value="<?= e($mask('pay_freekassa_api_key')) ?>" autocomplete="new-password"></div>
    </div>
    <div class="row">
      <div><label>Secret word 2 (webhook)</label><input name="pay_freekassa_secret_word_2" value="<?= e($mask('pay_freekassa_secret_word_2')) ?>" autocomplete="new-password"></div>
      <div><label>API base</label><input name="pay_freekassa_api_base" value="<?= e($s['pay_freekassa_api_base'] ?? 'https://api.fk.life/v1') ?>"></div>
    </div>
    <p style="font-size:0.85rem;margin:0.5rem 0 0">Webhook: <code><?= e(app_url('/webhooks/freekassa')) ?></code></p>
  </div>

  <div class="panel-card" style="margin-bottom:1rem">
    <h2 style="font-size:1.05rem;margin-top:0">
      Platega
      <span class="pill"><?= !empty($enabled['platega']) ? 'вкл' : 'выкл' ?></span>
    </h2>
    <label><input type="checkbox" name="pay_platega_on" value="1" <?= $flag('pay_platega_on', true) ? 'checked' : '' ?>> Включить</label>
    <div class="row">
      <div><label>Merchant ID</label><input name="pay_platega_merchant_id" value="<?= e($s['pay_platega_merchant_id'] ?? '') ?>"></div>
      <div><label>Secret</label><input name="pay_platega_secret" value="<?= e($mask('pay_platega_secret')) ?>" autocomplete="new-password"></div>
    </div>
    <div class="row">
      <div><label>API base</label><input name="pay_platega_api_base" value="<?= e($s['pay_platega_api_base'] ?? 'https://app.platega.io') ?>"></div>
      <div><label>paymentMethod (опц.)</label><input name="pay_platega_payment_method" value="<?= e($s['pay_platega_payment_method'] ?? '') ?>"></div>
    </div>
    <p style="font-size:0.85rem;margin:0.5rem 0 0">Webhook: <code><?= e(app_url('/webhooks/platega')) ?></code></p>
  </div>

  <div class="panel-card" style="margin-bottom:1rem">
    <h2 style="font-size:1.05rem;margin-top:0">
      NOWPayments (crypto)
      <span class="pill"><?= !empty($enabled['nowpayments']) ? 'вкл' : 'выкл' ?></span>
    </h2>
    <label><input type="checkbox" name="pay_nowpayments_on" value="1" <?= $flag('pay_nowpayments_on', true) ? 'checked' : '' ?>> Включить</label>
    <div class="row">
      <div><label>API key</label><input name="pay_nowpayments_api_key" value="<?= e($mask('pay_nowpayments_api_key')) ?>" autocomplete="new-password"></div>
      <div><label>IPN secret</label><input name="pay_nowpayments_ipn_secret" value="<?= e($mask('pay_nowpayments_ipn_secret')) ?>" autocomplete="new-password"></div>
    </div>
    <div class="row">
      <div><label>pay_currency</label><input name="pay_nowpayments_pay_currency" value="<?= e($s['pay_nowpayments_pay_currency'] ?? 'usdttrc20') ?>"></div>
      <div><label>Курс USD (RUB→USD)</label><input name="usd_rate" value="<?= e($s['usd_rate'] ?? '90') ?>"></div>
    </div>
    <p style="font-size:0.85rem;margin:0.5rem 0 0">Webhook: <code><?= e(app_url('/webhooks/nowpayments')) ?></code></p>
  </div>

  <button class="btn btn-primary" type="submit">Сохранить платёжные системы</button>
  <a class="btn btn-ghost" href="/admin/payments">К списку платежей</a>
</form>
