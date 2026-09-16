<section class="section container">
  <div class="section-head">
    <h2>Каталог серверов</h2>
    <p>Фильтры по хостеру, городу и наличию. Один лот — один заказ.</p>
  </div>
  <form class="filters" method="get" action="/catalog">
    <select name="vendor">
      <option value="">Все хостеры</option>
      <?php foreach ($vendors as $v): ?>
        <option value="<?= e($v) ?>" <?= ($filterVendor ?? '') === $v ? 'selected' : '' ?>><?= e($v) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="location">
      <option value="">Все города</option>
      <?php foreach ($locations as $loc): ?>
        <option value="<?= e($loc) ?>" <?= ($filterLocation ?? '') === $loc ? 'selected' : '' ?>><?= e($loc) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status">
      <option value="">Любой статус</option>
      <option value="available" <?= ($filterStatus ?? '') === 'available' ? 'selected' : '' ?>>В наличии</option>
      <option value="preorder" <?= ($filterStatus ?? '') === 'preorder' ? 'selected' : '' ?>>Под заказ</option>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Применить</button>
  </form>
  <div class="product-grid">
    <?php if (!$products): ?>
      <p>По фильтрам ничего не найдено.</p>
    <?php endif; ?>
    <?php foreach ($products as $p): ?>
      <?php require BASE_PATH . '/views/partials/product_card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>
