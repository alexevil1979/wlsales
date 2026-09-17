<div class="fi-header">
  <div>
    <h2 class="fi-header-heading" style="font-size:1.25rem;margin:0">Товары</h2>
    <p class="fi-header-sub">Лоты VPS / dedicated на витрине</p>
  </div>
  <div class="fi-header-actions">
    <a class="btn btn-primary" href="/admin/products/create">Создать товар</a>
  </div>
</div>
<div class="table-wrap">
  <table class="data">
    <thead><tr><th>ID</th><th>Название</th><th>Хостер</th><th>Цены</th><th>Статус</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><?= (int)$p['id'] ?></td>
        <td><?= e($p['title']) ?><br><small class="text-muted"><?= e($p['slug']) ?></small></td>
        <td><?= e($p['vendor']) ?><br><small class="text-muted"><?= e($p['location']) ?></small></td>
        <td><?= money($p['price_rent']) ?> / <?= money($p['price_forever']) ?></td>
        <td><span class="pill"><?= e(product_status_label($p['status'])) ?></span></td>
        <td style="white-space:nowrap">
          <a class="btn btn-ghost btn-sm" href="/admin/products/<?= (int)$p['id'] ?>/edit">Изменить</a>
          <form method="post" action="/admin/products/<?= (int)$p['id'] ?>/delete" style="display:inline" onsubmit="return confirm('Удалить?')">
            <?= \App\Core\Csrf::field() ?>
            <button class="btn btn-danger btn-sm" type="submit">Удалить</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
