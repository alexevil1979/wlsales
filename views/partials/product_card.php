<?php
/** @var array $p */
$status = $p['status'] ?? '';
$statusClass = match ($status) {
    'available' => '',
    'preorder' => 'warn',
    'reserved' => 'muted',
    'sold' => 'danger',
    default => 'muted',
};
?>
<article class="product-card reveal" data-reveal>
  <div class="meta">
    <span class="pill"><?= e($p['vendor']) ?></span>
    <span class="pill muted"><?= e($p['location']) ?></span>
    <span class="pill <?= e($statusClass) ?>"><?= e(product_status_label($status)) ?></span>
  </div>
  <h3><a href="/catalog/<?= e($p['slug']) ?>" style="color:inherit"><?= e($p['title']) ?></a></h3>
  <?php if ($p['subnet']): ?><div class="subnet"><?= e($p['subnet']) ?></div><?php endif; ?>
  <table class="specs">
    <tr><th>CPU</th><td><?= e($p['cpu']) ?></td></tr>
    <tr><th>RAM</th><td><?= (int)$p['ram_gb'] ?> ГБ</td></tr>
    <tr><th>Диск</th><td><?= (int)$p['disk_gb'] ?> ГБ</td></tr>
    <tr><th>Канал</th><td><?= e($p['nic']) ?> · <?= e($p['traffic']) ?></td></tr>
  </table>
  <div class="prices">
    <div class="price-box"><span class="lbl">Аренда</span><span class="val"><?= money($p['price_rent']) ?></span></div>
    <div class="price-box"><span class="lbl">Рассрочка</span><span class="val"><?= money($p['price_inst_2']) ?></span></div>
    <div class="price-box accent"><span class="lbl">Навсегда</span><span class="val"><?= money($p['price_forever']) ?></span></div>
  </div>
  <a class="btn btn-ghost btn-sm" href="/catalog/<?= e($p['slug']) ?>">Открыть лот</a>
</article>
