<?php
$canBuy = in_array($product['status'], ['available', 'preorder'], true);
?>
<section class="section container">
  <p><a href="/catalog">← Каталог</a></p>
  <div class="grid-2" style="align-items:start;gap:1.5rem">
    <div>
      <div class="meta" style="display:flex;gap:0.4rem;flex-wrap:wrap;margin-bottom:0.8rem">
        <span class="pill"><?= e($product['vendor']) ?></span>
        <span class="pill muted"><?= e($product['location']) ?></span>
        <span class="pill"><?= e(product_status_label($product['status'])) ?></span>
      </div>
      <h1><?= e($product['title']) ?></h1>
      <?php if ($product['subnet']): ?><p class="subnet" style="font-family:var(--mono);color:var(--accent)"><?= e($product['subnet']) ?></p><?php endif; ?>
      <table class="specs" style="max-width:420px;margin:1rem 0">
        <tr><th>CPU</th><td><?= e($product['cpu']) ?></td></tr>
        <tr><th>RAM</th><td><?= (int)$product['ram_gb'] ?> ГБ</td></tr>
        <tr><th>Диск</th><td><?= (int)$product['disk_gb'] ?> ГБ</td></tr>
        <tr><th>Сеть</th><td><?= e($product['nic']) ?></td></tr>
        <tr><th>Трафик</th><td><?= e($product['traffic']) ?></td></tr>
      </table>
      <div class="card-soft"><?= nl2br(e((string)$product['description'])) ?></div>
      <p class="disclaimer">IP проверяется перед выдачей. При проблеме с доступностью — замена по регламенту через тикет.</p>
    </div>
    <aside class="card-soft" style="position:sticky;top:5rem">
      <h2 style="font-size:1.15rem">Тарифы</h2>
      <div class="prices" style="grid-template-columns:1fr;margin:1rem 0">
        <div class="price-box"><span class="lbl">Аренда / мес</span><span class="val"><?= money($product['price_rent']) ?></span></div>
        <div class="price-box"><span class="lbl">Рассрочка 2 нед.</span><span class="val"><?= money($product['price_inst_2']) ?></span></div>
        <div class="price-box"><span class="lbl">Рассрочка 4 нед.</span><span class="val"><?= money($product['price_inst_4']) ?></span></div>
        <div class="price-box accent"><span class="lbl">Выкуп навсегда</span><span class="val"><?= money($product['price_forever']) ?></span></div>
      </div>
      <?php if ($canBuy): ?>
        <?php if (\App\Core\Auth::check()): ?>
          <form method="post" action="/checkout/<?= (int)$product['id'] ?>" class="form">
            <?= \App\Core\Csrf::field() ?>
            <label>Выберите тариф</label>
            <select name="tariff" required>
              <option value="rent">Аренда — <?= money($product['price_rent']) ?></option>
              <option value="inst2">Рассрочка 2 нед. — <?= money($product['price_inst_2']) ?></option>
              <option value="inst4">Рассрочка 4 нед. — <?= money($product['price_inst_4']) ?></option>
              <option value="forever">Навсегда — <?= money($product['price_forever']) ?></option>
            </select>
            <p style="margin-top:1rem"><button class="btn btn-primary" type="submit" style="width:100%">Купить</button></p>
          </form>
        <?php else: ?>
          <p><a class="btn btn-primary" href="/login" style="width:100%">Войти, чтобы купить</a></p>
          <p style="font-size:0.85rem;text-align:center"><a href="/register">Или зарегистрироваться</a></p>
        <?php endif; ?>
      <?php else: ?>
        <p class="flash flash-error" style="margin:0">Лот недоступен для покупки.</p>
      <?php endif; ?>
    </aside>
  </div>
</section>
