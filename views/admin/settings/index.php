<?php $s = $settings; ?>
<form method="post" action="/admin/settings" class="form card-soft">
  <?= \App\Core\Csrf::field() ?>
  <div class="row">
    <div><label>Telegram</label><input name="site_telegram" value="<?= e($s['site_telegram'] ?? '') ?>"></div>
    <div><label>Email</label><input name="site_email" value="<?= e($s['site_email'] ?? '') ?>"></div>
  </div>
  <label>Телефон</label>
  <input name="site_phone" value="<?= e($s['site_phone'] ?? '') ?>">
  <label>Реквизиты</label>
  <textarea name="requisites" rows="4"><?= e($s['requisites'] ?? '') ?></textarea>
  <div class="row">
    <div><label>СБП телефон</label><input name="sbp_phone" value="<?= e($s['sbp_phone'] ?? '') ?>"></div>
    <div><label>СБП комментарий</label><input name="sbp_comment" value="<?= e($s['sbp_comment'] ?? '') ?>"></div>
  </div>
  <label>USDT TRC20</label>
  <input name="crypto_usdt_trc20" value="<?= e($s['crypto_usdt_trc20'] ?? '') ?>">
  <label>Оферта (HTML)</label>
  <textarea name="offer_html" rows="6"><?= e($s['offer_html'] ?? '') ?></textarea>
  <label>Персональные данные (HTML)</label>
  <textarea name="privacy_html" rows="6"><?= e($s['privacy_html'] ?? '') ?></textarea>
  <p style="margin-top:1rem"><button class="btn btn-primary" type="submit">Сохранить</button></p>
  <p style="font-size:0.85rem;color:var(--muted)">ЮKassa включается ключами в <code>.env</code> (YOOKASSA_SHOP_ID / YOOKASSA_SECRET_KEY).</p>
</form>
