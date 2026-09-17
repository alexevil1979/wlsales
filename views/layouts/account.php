<?php
/** @var string $content */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$user = \App\Core\Auth::user();
$link = static function (string $href, string $label) use ($path): string {
    $active = $href === '/account'
        ? ($path === '/account')
        : str_starts_with($path, $href);
    return '<a href="' . e($href) . '" class="' . ($active ? 'is-active' : '') . '">' . e($label) . '</a>';
};
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Кабинет') ?> — WL Sales</title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/panel.css">
</head>
<body class="panel-body panel-account">
<div class="panel-shell">
  <aside class="panel-sidebar">
    <a class="panel-brand" href="/account"><span class="dot">WL</span> Кабинет</a>
    <nav class="panel-nav">
      <div class="nav-section">Аккаунт</div>
      <?= $link('/account', 'Обзор') ?>
      <?= $link('/account/orders', 'Заказы') ?>
      <?= $link('/account/servers', 'Серверы') ?>
      <?= $link('/account/proxy', 'Белый вход') ?>
      <?= $link('/account/tickets', 'Тикеты') ?>
      <?= $link('/account/password', 'Пароль') ?>
      <div class="nav-section">Магазин</div>
      <a href="/catalog">Каталог VPS</a>
      <a href="/proxy">Тарифы входа</a>
      <a href="/">На сайт</a>
    </nav>
    <form action="/logout" method="post" style="margin-top:1rem;padding:0 0.7rem">
      <?= \App\Core\Csrf::field() ?>
      <button class="btn btn-ghost btn-sm" type="submit" style="width:100%">Выйти</button>
    </form>
  </aside>
  <div class="panel-main">
    <header class="panel-topbar">
      <h1><?= e($title ?? 'Кабинет') ?></h1>
      <div class="meta"><?= e($user['name'] ?? '') ?> · <?= e($user['email'] ?? '') ?></div>
    </header>
    <div class="panel-content">
      <?php if ($m = flash('success')): ?><div class="flash flash-success"><?= e($m) ?></div><?php endif; ?>
      <?php if ($m = flash('error')): ?><div class="flash flash-error"><?= e($m) ?></div><?php endif; ?>
      <?= $content ?>
    </div>
  </div>
</div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
