<p><a class="btn btn-primary btn-sm" href="/admin/products/create">+ Товар</a></p>
<div class="table-wrap card-soft">
  <table class="data">
    <thead><tr><th>ID</th><th>Название</th><th>Хостер</th><th>Цены</th><th>Статус</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><?= (int)$p['id'] ?></td>
        <td><?= e($p['title']) ?><br><small style="color:var(--muted)"><?= e($p['slug']) ?></small></td>
        <td><?= e($p['vendor']) ?><br><?= e($p['location']) ?></td>
        <td><?= money($p['price_rent']) ?> / <?= money($p['price_forever']) ?></td>
        <td><?= e(product_status_label($p['status'])) ?></td>
        <td>
          <a href="/admin/products/<?= (int)$p['id'] ?>/edit">Изменить</a>
          <form method="post" action="/admin/products/<?= (int)$p['id'] ?>/delete" style="display:inline" onsubmit="return confirm('Удалить?')">
            <?= \App\Core\Csrf::field() ?>
            <button class="btn btn-danger btn-sm" type="submit">×</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
