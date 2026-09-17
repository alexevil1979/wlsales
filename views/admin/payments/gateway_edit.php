<?php
/** @var array $gw */
/** @var list<array> $fields */
/** @var string $webhook */
/** @var bool $hasDriver */
$cfg = is_array($gw['config'] ?? null) ? $gw['config'] : [];
?>
<div class="fi-header">
  <div>
    <h2 class="fi-header-heading" style="font-size:1.25rem;margin:0"><?= e($gw['name']) ?></h2>
    <p class="fi-header-sub"><code><?= e($gw['code']) ?></code>
      <?php if (!$hasDriver): ?> · драйвер оплаты ещё не подключён (ключи можно сохранить заранее)<?php endif; ?>
    </p>
  </div>
  <div class="fi-header-actions">
    <a class="btn btn-ghost" href="/admin/payments/gateways">К списку</a>
  </div>
</div>

<form method="post" action="/admin/payments/gateways/<?= (int)$gw['id'] ?>" class="form panel-card">
  <?= \App\Core\Csrf::field() ?>

  <h2 class="fi-section-title">Основные</h2>
  <label>Название (для клиента)</label>
  <input name="name" value="<?= e($gw['name']) ?>" required>
  <div class="row">
    <div>
      <label><input type="checkbox" name="enabled" value="1" <?= !empty($gw['enabled']) ? 'checked' : '' ?>> Включён</label>
    </div>
    <div>
      <label><input type="checkbox" name="test_mode" value="1" <?= !empty($gw['test_mode']) ? 'checked' : '' ?>> Тестовый режим</label>
    </div>
  </div>
  <label>Мин. сумма (RUB)</label>
  <input name="min_amount_rub" type="number" step="0.01" min="0" value="<?= e((string)$gw['min_amount_rub']) ?>">

  <h2 class="fi-section-title" style="margin-top:1.4rem">Config</h2>
  <p class="fi-section-desc">Секреты не затираются, если оставить поле пустым или <code>********</code>.</p>
  <?php foreach ($fields as $field):
      $key = $field['key'];
      $val = (string) ($cfg[$key] ?? ($field['default'] ?? ''));
      $isSecret = !empty($field['secret']);
      $show = $isSecret && $val !== '' ? '********' : $val;
  ?>
    <label><?= e($field['label']) ?></label>
    <input
      name="config[<?= e($key) ?>]"
      value="<?= e($show) ?>"
      <?= $isSecret ? 'type="password" autocomplete="new-password"' : 'autocomplete="off"' ?>
    >
    <?php if (!empty($field['help'])): ?>
      <p class="fi-section-desc" style="margin-top:0.25rem"><?= e($field['help']) ?></p>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php if ($webhook !== ''): ?>
    <p style="margin-top:1rem;font-size:0.85rem">Webhook URL: <code><?= e($webhook) ?></code></p>
  <?php endif; ?>

  <p style="margin-top:1.25rem">
    <button class="btn btn-primary" type="submit">Сохранить</button>
  </p>
</form>
