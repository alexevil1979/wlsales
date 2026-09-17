<?php
/** @var array $settings */
$s = $settings;
$maskPass = trim((string) ($s['mail_smtp_pass'] ?? '')) !== '' ? '********' : '';
$smtpOn = array_key_exists('mail_smtp_on', $s)
    ? (($s['mail_smtp_on'] ?? '0') === '1')
    : (mail_cfg('mail_smtp_host', 'MAIL_SMTP_HOST') !== '');
?>
<p class="fi-section-desc" style="margin-top:0">Как SMTP settings в fullvpnservice: параметры из админки имеют приоритет над <code>.env</code>. Без PHPMailer используется встроенный SMTP-клиент.</p>

<form method="post" action="/admin/mail" class="form">
  <?= \App\Core\Csrf::field() ?>

  <div class="panel-card" style="margin-bottom:1rem">
    <h2 class="fi-section-title">SMTP</h2>
    <label><input type="checkbox" name="mail_smtp_on" value="1" <?= $smtpOn ? 'checked' : '' ?>> Включить SMTP (иначе — системный <code>mail()</code>)</label>
    <div class="row">
      <div><label>Host</label><input name="mail_smtp_host" value="<?= e($s['mail_smtp_host'] ?? mail_cfg('mail_smtp_host', 'MAIL_SMTP_HOST')) ?>" placeholder="smtp.gmail.com"></div>
      <div><label>Port</label><input name="mail_smtp_port" value="<?= e($s['mail_smtp_port'] ?? mail_cfg('mail_smtp_port', 'MAIL_SMTP_PORT', '587')) ?>"></div>
    </div>
    <div class="row">
      <div>
        <label>Encryption</label>
        <?php $enc = $s['mail_smtp_secure'] ?? mail_cfg('mail_smtp_secure', 'MAIL_SMTP_SECURE', 'tls'); ?>
        <select name="mail_smtp_secure">
          <option value="tls" <?= $enc === 'tls' ? 'selected' : '' ?>>TLS</option>
          <option value="ssl" <?= $enc === 'ssl' ? 'selected' : '' ?>>SSL</option>
          <option value="" <?= $enc === '' ? 'selected' : '' ?>>Нет</option>
        </select>
      </div>
      <div><label>Username</label><input name="mail_smtp_user" value="<?= e($s['mail_smtp_user'] ?? mail_cfg('mail_smtp_user', 'MAIL_SMTP_USER')) ?>" autocomplete="off"></div>
    </div>
    <label>Password</label>
    <input name="mail_smtp_pass" type="password" value="<?= e($maskPass) ?>" autocomplete="new-password" placeholder="оставьте пустым, чтобы не менять">
    <div class="row">
      <div><label>From email</label><input name="mail_from" value="<?= e($s['mail_from'] ?? mail_cfg('mail_from', 'MAIL_FROM', 'noreply@localhost')) ?>"></div>
      <div><label>From name</label><input name="mail_from_name" value="<?= e($s['mail_from_name'] ?? mail_cfg('mail_from_name', 'MAIL_FROM_NAME', 'WL Sales')) ?>"></div>
    </div>
  </div>

  <div class="panel-card" style="margin-bottom:1rem">
    <h2 class="fi-section-title">Уведомления по тикетам</h2>
    <label><input type="checkbox" name="mail_notify_ticket_opened" value="1" <?= (($s['mail_notify_ticket_opened'] ?? '1') === '1') ? 'checked' : '' ?>> Письмо клиенту при создании тикета</label>
    <label><input type="checkbox" name="mail_notify_admin_reply" value="1" <?= (($s['mail_notify_admin_reply'] ?? '1') === '1') ? 'checked' : '' ?>> Письмо клиенту при ответе поддержки</label>
    <p style="font-size:0.85rem;margin:0.5rem 0 0;color:#6b7280">Админу по-прежнему уходит Telegram (если включено в Настройках).</p>
  </div>

  <div class="panel-card" style="margin-bottom:1rem">
    <h2 class="fi-section-title">Тест</h2>
    <label>Email для теста</label>
    <input name="mail_test_to" value="<?= e($s['mail_test_to'] ?? (\App\Core\Auth::user()['email'] ?? '')) ?>">
  </div>

  <p>
    <button class="btn btn-primary" type="submit">Сохранить</button>
    <button class="btn btn-ghost" type="submit" name="mail_test" value="1">Сохранить и отправить тест</button>
  </p>
</form>
