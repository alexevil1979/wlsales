<?php
/** @var list<array> $gateways */
/** @var array<string, string> $drivers */
?>
<div class="fi-header">
  <div>
    <h2 class="fi-header-heading" style="font-size:1.25rem;margin:0">Платёжные шлюзы</h2>
    <p class="fi-header-sub">Как в fullvpnservice: каждый шлюз — отдельная запись (вкл/выкл, тест, мин. сумма, config).</p>
  </div>
</div>

<div class="table-wrap">
  <table class="data">
    <thead>
      <tr>
        <th>Код</th>
        <th>Имя</th>
        <th>Вкл.</th>
        <th>Тест</th>
        <th>От суммы</th>
        <th>Драйвер</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($gateways as $g): ?>
      <?php $code = (string) $g['code']; ?>
      <tr>
        <td><code><?= e($code) ?></code></td>
        <td><?= e($g['name']) ?></td>
        <td>
          <form method="post" action="/admin/payments/gateways/<?= (int)$g['id'] ?>/toggle" style="display:inline">
            <?= \App\Core\Csrf::field() ?>
            <button class="btn btn-sm <?= !empty($g['enabled']) ? 'btn-primary' : 'btn-ghost' ?>" type="submit">
              <?= !empty($g['enabled']) ? 'Да' : 'Нет' ?>
            </button>
          </form>
        </td>
        <td><?= !empty($g['test_mode']) ? 'да' : 'нет' ?></td>
        <td><?= number_format((float)$g['min_amount_rub'], 2, '.', ' ') ?> ₽</td>
        <td>
          <?php if (isset($drivers[$code])): ?>
            <span class="pill">OK</span>
          <?php else: ?>
            <span class="text-muted">настройка</span>
          <?php endif; ?>
        </td>
        <td><a class="btn btn-ghost btn-sm" href="/admin/payments/gateways/<?= (int)$g['id'] ?>">Изменить</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
