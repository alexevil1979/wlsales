<?php
/** @var string $content */
/** @var string $title */
$user = \App\Core\Auth::user();
$desc = $description ?? 'Серверы с проверенным публичным IP — аренда и выкуп.';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'WL Sales') ?></title>
  <meta name="description" content="<?= e($desc) ?>">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="logo" href="/"><span></span>WL Sales</a>
    <button class="nav-toggle" type="button" data-nav-toggle aria-label="Меню">Меню</button>
    <nav class="nav" data-nav>
      <a href="/catalog" class="<?= str_starts_with($path, '/catalog') ? 'is-active' : '' ?>">Каталог</a>
      <a href="/proxy" class="<?= str_starts_with($path, '/proxy') ? 'is-active' : '' ?>">Белый вход для сайта</a>
      <a href="/how" class="<?= $path === '/how' ? 'is-active' : '' ?>">Как это работает</a>
      <a href="/faq" class="<?= $path === '/faq' ? 'is-active' : '' ?>">FAQ</a>
      <?php if ($user): ?>
        <a href="/account">Кабинет</a>
        <?php if (($user['role'] ?? '') === 'admin'): ?><a href="/admin">Админ</a><?php endif; ?>
        <form action="/logout" method="post" style="display:inline"><?= \App\Core\Csrf::field() ?><button class="btn btn-ghost btn-sm" type="submit">Выйти</button></form>
      <?php else: ?>
        <a href="/login">Войти</a>
      <?php endif; ?>
      <a class="btn btn-primary btn-sm" href="/catalog">Выбрать сервер</a>
    </nav>
  </div>
</header>

<main>
  <div class="container">
    <?php if ($m = flash('success')): ?><div class="flash flash-success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash('error')): ?><div class="flash flash-error"><?= e($m) ?></div><?php endif; ?>
  </div>
  <?= $content ?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <div class="logo" style="margin-bottom:0.6rem"><span></span>WL Sales</div>
      <p>Серверы с IP из проверенных российских подсетей. Проверка доступности перед выдачей. Замена IP при проблеме — по регламенту через тикет.</p>
    </div>
    <div>
      <strong style="color:var(--text)">Разделы</strong><br>
      <a href="/catalog">Каталог</a><br>
      <a href="/proxy">Белый вход для сайта</a><br>
      <a href="/how">Как это работает</a><br>
      <a href="/faq">FAQ</a><br>
      <a href="/contacts">Контакты</a>
    </div>
    <div>
      <strong style="color:var(--text)">Документы</strong><br>
      <a href="/legal/offer">Оферта</a><br>
      <a href="/legal/privacy">Персональные данные</a><br>
      <span style="display:block;margin-top:0.6rem;white-space:pre-line;font-size:0.8rem"><?= e(setting('requisites', 'Реквизиты уточняются')) ?></span>
    </div>
  </div>
</footer>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
