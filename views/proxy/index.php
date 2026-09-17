<section class="section container">
  <div class="section-head">
    <h1>Белый вход для сайта</h1>
    <p>Сайт на обычном хостинге может плохо открываться у части пользователей. Первый hop — наш проверенный публичный IP: вы ставите A-запись на наш адрес, мы принимаем HTTPS и проксируем на ваш origin.</p>
  </div>

  <div class="grid-3" style="margin-bottom:2rem">
    <article class="card-soft reveal" data-reveal>
      <h3>1. DNS A</h3>
      <p>Указываете A @ и www на наш публичный IP. Cloudflare — только «серое облако» (DNS only).</p>
    </article>
    <article class="card-soft reveal" data-reveal>
      <h3>2. Наш IP:443</h3>
      <p>На входе TLS/SNI. Несколько ваших доменов могут жить на одном IP.</p>
    </article>
    <article class="card-soft reveal" data-reveal>
      <h3>3. Ваш origin</h3>
      <p>Трафик уходит на ваш хост/IP:порт. Origin должен отвечать с нашего VPS.</p>
    </article>
  </div>

  <p class="disclaimer">Вход с проверенного публичного IP. Проверка доступности перед подключением. Замена входа — по регламенту через тикет. Мы не обещаем «обход любых ограничений навсегда».</p>

  <div class="section-head" style="margin-top:2rem">
    <h2>Калькулятор</h2>
    <p>Сколько доменов нужно — подскажем подходящий план.</p>
  </div>
  <form class="filters" method="get" action="/proxy">
    <label style="color:var(--muted)">Доменов</label>
    <input type="number" name="domains" min="1" max="50" value="<?= (int)$domainsWanted ?>" style="width:6rem;background:var(--bg-soft);border:1px solid var(--line);color:var(--text);border-radius:10px;padding:0.55rem 0.7rem">
    <button class="btn btn-primary btn-sm" type="submit">Подобрать</button>
  </form>
  <?php if ($suggested): ?>
    <p>Рекомендуем: <strong><?= e($suggested['title']) ?></strong> — <?= money($suggested['price_month']) ?>/мес (до <?= (int)$suggested['domains_limit'] ?> дом.)</p>
  <?php endif; ?>

  <div class="section-head" style="margin-top:2rem">
    <h2 id="plans">Тарифы входа</h2>
  </div>
  <div class="product-grid">
    <?php foreach ($plans as $plan): ?>
      <article class="product-card reveal" data-reveal>
        <div class="meta">
          <span class="pill"><?= (int)$plan['dedicated_ip'] ? 'Выделенный IP' : 'Общий IP' ?></span>
          <span class="pill muted">до <?= (int)$plan['domains_limit'] ?> дом.</span>
        </div>
        <h3><?= e($plan['title']) ?></h3>
        <div class="prices" style="grid-template-columns:1fr">
          <div class="price-box accent"><span class="lbl">В месяц</span><span class="val"><?= money($plan['price_month']) ?></span></div>
        </div>
        <?php if (\App\Core\Auth::check()): ?>
          <form method="post" action="/proxy/checkout/<?= (int)$plan['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <button class="btn btn-primary btn-sm" type="submit" style="width:100%">Подключить домен</button>
          </form>
        <?php else: ?>
          <a class="btn btn-primary btn-sm" href="/login">Войти, чтобы подключить</a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="section-head" style="margin-top:2.5rem">
    <h2>FAQ</h2>
  </div>
  <div class="faq">
    <details open>
      <summary>Можно несколько доменов на одном IP?</summary>
      <p>Да. Один публичный адрес обслуживает несколько сайтов по SNI. Лимит — по тарифу.</p>
    </details>
    <details>
      <summary>Что если IP перестал проходить проверку?</summary>
      <p>Откройте тикет. Замена входа — по регламенту при наличии свободной ноды.</p>
    </details>
    <details>
      <summary>Кто выпускает сертификат?</summary>
      <p>HTTPS на входной ноде поднимает администратор (обычно Let’s Encrypt / certbot). В кабинете виден статус SSL.</p>
    </details>
    <details>
      <summary>Как сменить origin?</summary>
      <p>Напишите в тикет «сменить origin» или укажите новые данные — админ обновит прокси и сниппет.</p>
    </details>
    <details>
      <summary>Origin должен быть доступен откуда?</summary>
      <p>С нашего VPS. Если origin режет входящие — откройте доступ с IP ноды или по согласованию.</p>
    </details>
  </div>

  <p style="margin-top:1.5rem">
    <a class="btn btn-primary" href="#plans">Смотреть тарифы входа</a>
    <a class="btn btn-ghost" href="/catalog">Каталог VPS</a>
  </p>
</section>
