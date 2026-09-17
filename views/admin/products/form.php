<?php $p = $product; ?>
<form method="post" class="form panel-card" action="<?= $p ? '/admin/products/' . (int)$p['id'] . '/edit' : '/admin/products/create' ?>">
  <?= \App\Core\Csrf::field() ?>
  <h2 class="fi-section-title"><?= $p ? 'Редактирование товара' : 'Новый товар' ?></h2>
  <p class="fi-section-desc">Карточка лота на витрине каталога.</p>
  <div class="row">
    <div><label>Название</label><input name="title" required value="<?= e($p['title'] ?? '') ?>"></div>
    <div><label>Slug</label><input name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="auto"></div>
  </div>
  <div class="row">
    <div><label>Хостер</label><input name="vendor" required value="<?= e($p['vendor'] ?? '') ?>"></div>
    <div><label>Город</label><input name="location" required value="<?= e($p['location'] ?? '') ?>"></div>
  </div>
  <div class="row">
    <div><label>Подсеть</label><input name="subnet" value="<?= e($p['subnet'] ?? '') ?>"></div>
    <div><label>CPU</label><input name="cpu" value="<?= e($p['cpu'] ?? '') ?>"></div>
  </div>
  <div class="row">
    <div><label>RAM ГБ</label><input name="ram_gb" type="number" value="<?= (int)($p['ram_gb'] ?? 0) ?>"></div>
    <div><label>Диск ГБ</label><input name="disk_gb" type="number" value="<?= (int)($p['disk_gb'] ?? 0) ?>"></div>
  </div>
  <div class="row">
    <div><label>NIC</label><input name="nic" value="<?= e($p['nic'] ?? '1 Gbit') ?>"></div>
    <div><label>Трафик</label><input name="traffic" value="<?= e($p['traffic'] ?? 'безлимит') ?>"></div>
  </div>
  <label>Описание</label>
  <textarea name="description" rows="4"><?= e($p['description'] ?? '') ?></textarea>
  <div class="row">
    <div><label>Аренда</label><input name="price_rent" value="<?= e((string)($p['price_rent'] ?? '')) ?>"></div>
    <div><label>Навсегда</label><input name="price_forever" value="<?= e((string)($p['price_forever'] ?? '')) ?>"></div>
  </div>
  <div class="row">
    <div><label>Рассрочка 2 нед.</label><input name="price_inst_2" value="<?= e((string)($p['price_inst_2'] ?? '')) ?>"></div>
    <div><label>Рассрочка 4 нед.</label><input name="price_inst_4" value="<?= e((string)($p['price_inst_4'] ?? '')) ?>"></div>
  </div>
  <div class="row">
    <div>
      <label>Статус</label>
      <select name="status">
        <?php foreach (['available','preorder','reserved','sold','hidden'] as $st): ?>
          <option value="<?= $st ?>" <?= ($p['status'] ?? '') === $st ? 'selected' : '' ?>><?= e(product_status_label($st)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label>Сортировка</label><input name="sort" type="number" value="<?= (int)($p['sort'] ?? 100) ?>"></div>
  </div>
  <p style="margin-top:1rem"><button class="btn btn-primary" type="submit">Сохранить</button>
    <a class="btn btn-ghost" href="/admin/products">Отмена</a></p>
</form>
