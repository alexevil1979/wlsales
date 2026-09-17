<?php
/** @var list<array> $gateways */
/** @var string $q */
/** @var string $filterEnabled */
/** @var string $filterTest */
/** @var string $filterType */
/** @var string $csrf */
?>
<div class="fi-header">
  <div>
    <h2 class="fi-header-heading">Платёжные шлюзы</h2>
  </div>
  <div class="fi-header-actions">
    <a class="btn btn-primary" href="/admin/payments/gateways/create">Создать</a>
  </div>
</div>

<div class="fi-ta-toolbar">
  <form method="get" action="/admin/payments/gateways" class="fi-ta-search" id="gw-filters">
    <svg class="fi-ta-search-ico" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.473 9.8l3.613 3.614a.75.75 0 1 0 1.06-1.06l-3.614-3.613A5.5 5.5 0 0 0 9 3.5ZM5.5 9a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0Z" clip-rule="evenodd"/></svg>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Поиск" autocomplete="off">
    <select name="enabled" onchange="this.form.submit()">
      <option value="">Включен: все</option>
      <option value="1" <?= $filterEnabled === '1' ? 'selected' : '' ?>>Да</option>
      <option value="0" <?= $filterEnabled === '0' ? 'selected' : '' ?>>Нет</option>
    </select>
    <select name="test_mode" onchange="this.form.submit()">
      <option value="">Тест: все</option>
      <option value="1" <?= $filterTest === '1' ? 'selected' : '' ?>>Да</option>
      <option value="0" <?= $filterTest === '0' ? 'selected' : '' ?>>Нет</option>
    </select>
    <select name="type" onchange="this.form.submit()">
      <option value="">Тип: все</option>
      <option value="payment" <?= $filterType === 'payment' ? 'selected' : '' ?>>Платежный</option>
      <option value="internal" <?= $filterType === 'internal' ? 'selected' : '' ?>>Внутренний</option>
      <option value="legacy" <?= $filterType === 'legacy' ? 'selected' : '' ?>>Legacy (ЮKassa и др.)</option>
    </select>
    <button class="btn btn-ghost btn-sm" type="submit">Найти</button>
  </form>
</div>

<div class="table-wrap fi-ta">
  <table class="data">
    <thead>
      <tr>
        <th class="fi-ta-check"><input type="checkbox" data-check-all aria-label="Выбрать все"></th>
        <th>Код</th>
        <th>Имя</th>
        <th>Boosty slug</th>
        <th>Вкл.</th>
        <th>Тест</th>
        <th>От суммы</th>
        <th style="text-align:right">Доп. комиссия, %</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$gateways): ?>
      <tr><td colspan="9" class="text-muted" style="padding:1.5rem;text-align:center">Нет записей</td></tr>
    <?php endif; ?>
    <?php foreach ($gateways as $g): ?>
      <?php
        $code = (string) $g['code'];
        $cfg = is_array($g['config'] ?? null) ? $g['config'] : [];
        $boosty = (string) ($cfg['boosty_page_slug'] ?? '');
        $commission = (float) ($cfg['extra_commission_percent'] ?? 0);
      ?>
      <tr data-gw-id="<?= (int)$g['id'] ?>">
        <td class="fi-ta-check"><input type="checkbox" name="ids[]" value="<?= (int)$g['id'] ?>" data-row-check></td>
        <td>
          <button type="button" class="fi-copy-code" data-copy="<?= e($code) ?>" title="Копировать">
            <code><?= e($code) ?></code>
          </button>
        </td>
        <td><?= e($g['name']) ?></td>
        <td class="text-muted"><?= $boosty !== '' ? e($boosty) : '—' ?></td>
        <td>
          <button
            type="button"
            class="fi-toggle <?= !empty($g['enabled']) ? 'is-on' : '' ?>"
            data-toggle-field="enabled"
            data-toggle-id="<?= (int)$g['id'] ?>"
            role="switch"
            aria-checked="<?= !empty($g['enabled']) ? 'true' : 'false' ?>"
            title="Вкл."
          ><span class="fi-toggle-knob"></span></button>
        </td>
        <td>
          <button
            type="button"
            class="fi-toggle <?= !empty($g['test_mode']) ? 'is-on' : '' ?>"
            data-toggle-field="test_mode"
            data-toggle-id="<?= (int)$g['id'] ?>"
            role="switch"
            aria-checked="<?= !empty($g['test_mode']) ? 'true' : 'false' ?>"
            title="Тест"
          ><span class="fi-toggle-knob"></span></button>
        </td>
        <td><?= number_format((float)$g['min_amount_rub'], 2, '.', ' ') ?> RUB</td>
        <td style="text-align:right"><?= number_format($commission, 2, '.', '') ?>%</td>
        <td style="text-align:right">
          <a class="fi-icon-btn" href="/admin/payments/gateways/<?= (int)$g['id'] ?>" title="Изменить" aria-label="Изменить">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
(function () {
  var csrf = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE) ?>;

  document.querySelectorAll('[data-check-all]').forEach(function (master) {
    master.addEventListener('change', function () {
      document.querySelectorAll('[data-row-check]').forEach(function (cb) {
        cb.checked = master.checked;
      });
    });
  });

  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var t = btn.getAttribute('data-copy') || '';
      if (navigator.clipboard) navigator.clipboard.writeText(t);
    });
  });

  document.querySelectorAll('[data-toggle-field]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-toggle-id');
      var field = btn.getAttribute('data-toggle-field');
      var next = !btn.classList.contains('is-on');
      btn.disabled = true;
      var body = new URLSearchParams();
      body.set('_csrf', csrf);
      body.set('field', field);
      body.set('value', next ? '1' : '0');
      fetch('/admin/payments/gateways/' + id + '/toggle', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: body.toString()
      }).then(function (r) { return r.json(); }).then(function (data) {
        if (data && data.ok) {
          btn.classList.toggle('is-on', !!data.value);
          btn.setAttribute('aria-checked', data.value ? 'true' : 'false');
        }
      }).finally(function () { btn.disabled = false; });
    });
  });
})();
</script>
