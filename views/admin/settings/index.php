<?php
/** @var array $settings */
$s = $settings;
?>
<form method="post" action="/admin/settings" class="form panel-card">
  <?= \App\Core\Csrf::field() ?>
  <h2 style="font-size:1.05rem;margin-top:0">Контакты сайта</h2>
  <div class="row">
    <div><label>Telegram</label><input name="site_telegram" value="<?= e($s['site_telegram'] ?? '') ?>"></div>
    <div><label>Email</label><input name="site_email" value="<?= e($s['site_email'] ?? '') ?>"></div>
  </div>
  <label>Телефон</label>
  <input name="site_phone" value="<?= e($s['site_phone'] ?? '') ?>">
  <label>Реквизиты</label>
  <textarea name="requisites" rows="4"><?= e($s['requisites'] ?? '') ?></textarea>

  <h2 style="font-size:1.05rem;margin-top:1.4rem">Ручные платежи</h2>
  <div class="row">
    <div><label>СБП телефон</label><input name="sbp_phone" value="<?= e($s['sbp_phone'] ?? '') ?>"></div>
    <div><label>СБП комментарий</label><input name="sbp_comment" value="<?= e($s['sbp_comment'] ?? '') ?>"></div>
  </div>
  <label>USDT TRC20</label>
  <input name="crypto_usdt_trc20" value="<?= e($s['crypto_usdt_trc20'] ?? '') ?>">
  <label>Курс USD (для FreeKassa world / NOWPayments)</label>
  <input name="usd_rate" value="<?= e($s['usd_rate'] ?? '90') ?>">

  <h2 style="font-size:1.05rem;margin-top:1.4rem">Telegram-уведомления админу</h2>
  <p style="font-size:0.88rem">Как в fullvpnservice: бот шлёт события в чат. Токен/chat_id можно задать здесь или в <code>.env</code> (<code>TELEGRAM_BOT_TOKEN</code>, <code>TELEGRAM_ADMIN_CHAT_ID</code>).</p>
  <div class="row">
    <div><label>Bot token</label><input name="tg_bot_token" value="<?= e($s['tg_bot_token'] ?? '') ?>" autocomplete="off"></div>
    <div><label>Admin chat ID</label><input name="tg_admin_chat_id" value="<?= e($s['tg_admin_chat_id'] ?? '') ?>"></div>
  </div>
  <div class="row">
    <div><label><input type="checkbox" name="tg_notify_registrations" value="1" <?= (($s['tg_notify_registrations'] ?? '1') === '1') ? 'checked' : '' ?>> Регистрации</label></div>
    <div><label><input type="checkbox" name="tg_notify_payments" value="1" <?= (($s['tg_notify_payments'] ?? '1') === '1') ? 'checked' : '' ?>> Оплаты</label></div>
  </div>
  <div class="row">
    <div><label><input type="checkbox" name="tg_notify_deliveries" value="1" <?= (($s['tg_notify_deliveries'] ?? '1') === '1') ? 'checked' : '' ?>> Выдача доступов</label></div>
    <div><label><input type="checkbox" name="tg_notify_tickets" value="1" <?= (($s['tg_notify_tickets'] ?? '1') === '1') ? 'checked' : '' ?>> Тикеты</label></div>
  </div>

  <h2 style="font-size:1.05rem;margin-top:1.4rem">Документы</h2>
  <label>Оферта (HTML)</label>
  <textarea name="offer_html" rows="5"><?= e($s['offer_html'] ?? '') ?></textarea>
  <label>Персональные данные (HTML)</label>
  <textarea name="privacy_html" rows="5"><?= e($s['privacy_html'] ?? '') ?></textarea>

  <p style="margin-top:1rem">
    <button class="btn btn-primary" type="submit">Сохранить</button>
    <button class="btn btn-ghost" type="submit" name="tg_test" value="1">Тест Telegram</button>
  </p>
  <div class="panel-card" style="margin-top:1rem;background:#fffbeb;border-color:#fde68a">
    <strong>Платёжные шлюзы (.env)</strong>
    <p style="font-size:0.88rem;margin:0.5rem 0 0">Как в fullvpnservice — ключи в окружении, пустые = скрыты на оплате:</p>
    <ul style="font-size:0.85rem;margin:0.4rem 0 0;color:#4b5563">
      <li><code>YOOKASSA_SHOP_ID</code> / <code>YOOKASSA_SECRET_KEY</code> → webhook <code>/webhooks/yookassa</code></li>
      <li><code>FREEKASSA_SHOP_ID</code> / <code>FREEKASSA_API_KEY</code> / <code>FREEKASSA_SECRET_WORD_2</code> → <code>/webhooks/freekassa</code> (СБП, карты РФ, world USD)</li>
      <li><code>PLATEGA_MERCHANT_ID</code> / <code>PLATEGA_SECRET</code> → <code>/webhooks/platega</code></li>
      <li><code>NOWPAYMENTS_API_KEY</code> / <code>NOWPAYMENTS_IPN_SECRET</code> → <code>/webhooks/nowpayments</code></li>
    </ul>
  </div>
</form>
