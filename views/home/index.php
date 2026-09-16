<section class="hero container">
  <div class="hero-grid">
    <div>
      <h1>Серверы с проверенным публичным IP — аренда и выкуп</h1>
      <p class="lead">Выдача доступов в личный кабинет. Оплата СБП, картой или по счёту. IP из проверенных российских подсетей — с проверкой доступности перед передачей.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/catalog">В каталог</a>
        <a class="btn btn-ghost" href="/faq#check-ip">Как проверить IP</a>
      </div>
      <div class="badges">
        <span class="badge">Selectel</span>
        <span class="badge">Timeweb</span>
        <span class="badge">MWS</span>
        <span class="badge">Yandex Cloud</span>
      </div>
    </div>
    <aside class="hero-panel">
      <h3>Сейчас на витрине</h3>
      <ul>
        <?php foreach (array_slice($products, 0, 4) as $p): ?>
          <li>
            <span><?= e($p['vendor']) ?> · <?= e($p['location']) ?></span>
            <strong><?= money($p['price_rent']) ?></strong>
          </li>
        <?php endforeach; ?>
        <?php if (!$products): ?>
          <li><span>Каталог скоро пополнится</span><strong>—</strong></li>
        <?php endif; ?>
      </ul>
    </aside>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <h2>Три модели сделки</h2>
    <p>Выберите формат под срок и бюджет. Один лот — один заказ, без корзины.</p>
  </div>
  <div class="grid-3">
    <article class="card-soft reveal" data-reveal>
      <h3>Аренда / месяц</h3>
      <p>Когда нужен сервер на короткий срок или для теста. Продление — через тикет до окончания периода.</p>
    </article>
    <article class="card-soft reveal" data-reveal>
      <h3>Рассрочка 2 / 4 недели</h3>
      <p>Фиксированная сумма на срок. Выгоднее длинной аренды, если планируете держать машину дольше месяца.</p>
    </article>
    <article class="card-soft reveal" data-reveal>
      <h3>Выкуп навсегда</h3>
      <p>Разовая оплата — сервер и IP остаются у вас без ежемесячных платежей магазину (хостинг-абонентка у провайдера — по условиям лота).</p>
    </article>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <h2>Как покупают</h2>
    <p>Четыре шага от регистрации до доступов в кабинете.</p>
  </div>
  <div class="grid-4 steps">
    <article class="card-soft reveal" data-reveal><h3>Аккаунт</h3><p>Регистрация по email. Укажите Telegram для связи.</p></article>
    <article class="card-soft reveal" data-reveal><h3>Лот</h3><p>Выберите сервер и тариф: аренда, рассрочка или выкуп.</p></article>
    <article class="card-soft reveal" data-reveal><h3>Оплата</h3><p>СБП / карта / USDT — по доступным способам на сайте.</p></article>
    <article class="card-soft reveal" data-reveal><h3>Доступы</h3><p>После подтверждения оплаты админ выдаёт IP и панель в кабинет.</p></article>
  </div>
</section>

<section class="section container" id="catalog">
  <div class="section-head">
    <h2>Каталог</h2>
    <p>Актуальные лоты. Фильтры по хостеру, городу и наличию — в полном каталоге.</p>
  </div>
  <div class="product-grid">
    <?php foreach ($products as $p): ?>
      <?php require BASE_PATH . '/views/partials/product_card.php'; ?>
    <?php endforeach; ?>
  </div>
  <p style="margin-top:1.2rem"><a class="btn btn-ghost" href="/catalog">Весь каталог</a></p>
</section>

<section class="section container">
  <div class="section-head">
    <h2>Гарантии</h2>
  </div>
  <div class="grid-3">
    <article class="card-soft"><h3>Замена IP</h3><p>Если адрес перестал проходить вашу проверку — откройте тикет. Замена по регламенту при наличии свободного ресурса.</p></article>
    <article class="card-soft"><h3>Аптайм хостера</h3><p>Инфраструктура провайдера (Selectel, Timeweb, MWS и др.). Следим за статусом и передаём root / панель управления.</p></article>
    <article class="card-soft"><h3>Чистая ОС</h3><p>Выдача с чистой системой. Без предустановленных VPN-панелей и сторонних конфигов.</p></article>
  </div>
  <p class="disclaimer">IP проверяется перед выдачей. Мы не обещаем «обход блокировок навсегда» — формулируем честно: проверенные подсети и замена при проблеме.</p>
</section>

<section class="section container">
  <div class="section-head">
    <h2>FAQ</h2>
  </div>
  <div class="faq">
    <details><summary>Что такое белый IP и чем он отличается от NAT?</summary><p>Белый (публичный) IP маршрутизируется из интернета напрямую на сервер. NAT — общий адрес, порты проброшены через шлюз хостера.</p></details>
    <details><summary>Чем аренда отличается от выкупа?</summary><p>Аренда — помесячная модель. Выкуп — разовая оплата лота «навсегда» в рамках условий сделки.</p></details>
    <details id="check-ip"><summary>Как проверить IP перед покупкой?</summary><p>На карточке лота указана подсеть. После оплаты и до финальной выдачи адрес проходит нашу проверку доступности. Детали проверки можно уточнить в тикете.</p></details>
    <details><summary>Что если подсеть перестала проходить проверку?</summary><p>Откройте тикет «IP не проходит проверку». Рассмотрим замену по регламенту.</p></details>
  </div>
  <p style="margin-top:1rem"><a href="/faq">Все вопросы</a> · <a href="/contacts">Контакты / Telegram</a></p>
</section>
