<?php
/** @var string $content */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$user = \App\Core\Auth::user();
$email = (string) ($user['email'] ?? '');
$name = (string) ($user['name'] ?? '');
$initials = strtoupper(mb_substr($name !== '' ? $name : ($email !== '' ? $email : 'U'), 0, 1));

$link = static function (string $href, string $label) use ($path): string {
    $active = $href === '/account'
        ? ($path === '/account')
        : str_starts_with($path, $href);
    return '<a href="' . e($href) . '" class="' . ($active ? 'is-active' : '') . '"><span>' . e($label) . '</span></a>';
};
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Кабинет') ?> — WL Sales</title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/panel.css?v=2">
</head>
<body class="panel-body panel-account">
<div class="panel-sidebar-backdrop" data-sidebar-close></div>
<div class="panel-shell">
  <aside class="panel-sidebar" id="account-sidebar">
    <a class="panel-brand" href="/account">
      <span class="dot">WL</span>
      <span>Кабинет<span class="brand-sub">Account panel</span></span>
    </a>
    <nav class="panel-nav">
      <div class="nav-section">Аккаунт</div>
      <?= $link('/account', 'Обзор') ?>
      <?= $link('/account/orders', 'Заказы') ?>
      <?= $link('/account/servers', 'Серверы') ?>
      <?= $link('/account/proxy', 'Белый вход') ?>
      <?= $link('/account/tickets', 'Тикеты') ?>
      <?= $link('/account/password', 'Пароль') ?>
      <div class="nav-section">Магазин</div>
      <a href="/catalog"><span>Каталог VPS</span></a>
      <a href="/proxy"><span>Тарифы входа</span></a>
    </nav>
    <div class="panel-sidebar-foot">
      <a class="panel-footer-link" href="/">← На сайт</a>
      <form action="/logout" method="post" style="padding:0.35rem 0.25rem 0">
        <?= \App\Core\Csrf::field() ?>
        <button class="btn btn-ghost btn-sm" type="submit" style="width:100%">Выйти</button>
      </form>
    </div>
  </aside>
  <div class="panel-main">
    <header class="panel-topbar">
      <div class="panel-topbar-left">
        <button type="button" class="panel-menu-btn" data-sidebar-open aria-label="Меню">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </button>
        <div>
          <div class="panel-breadcrumbs">
            <a href="/account">Кабинет</a>
            <?php if (($title ?? '') !== '' && ($title ?? '') !== 'Кабинет'): ?>
              <span class="sep">/</span>
              <span class="cur"><?= e($title) ?></span>
            <?php endif; ?>
          </div>
          <h1><?= e($title ?? 'Кабинет') ?></h1>
        </div>
      </div>
      <div class="panel-user">
        <span class="panel-user-avatar"><?= e($initials) ?></span>
        <span class="meta"><?= e($name !== '' ? $name . ' · ' : '') ?><?= e($email) ?></span>
      </div>
    </header>
    <div class="panel-content">
      <?php if ($m = flash('success')): ?><div class="flash flash-success"><?= e($m) ?></div><?php endif; ?>
      <?php if ($m = flash('error')): ?><div class="flash flash-error"><?= e($m) ?></div><?php endif; ?>
      <?= $content ?>
    </div>
  </div>
</div>
<script src="/assets/js/panel-admin.js?v=2" defer></script>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
