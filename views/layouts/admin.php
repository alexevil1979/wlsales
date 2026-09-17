<?php
/** @var string $content */
/** @var string|null $title */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$user = \App\Core\Auth::user();
$email = (string) ($user['email'] ?? '');
$initials = strtoupper(mb_substr($email !== '' ? $email : 'A', 0, 1));

$ico = static function (string $name): string {
    $paths = [
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>',
        'cube' => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>',
        'cart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>',
        'card' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>',
        'globe' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>',
        'server' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>',
        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3.414-3.414c-.14-.14-.331-.22-.53-.22H7.5c-1.242 0-2.25-1.007-2.25-2.25V10.5c0-1.242 1.008-2.25 2.25-2.25h.375"/>',
        'bank' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/>',
        'mail' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>',
        'cog' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>',
    ];
    $d = $paths[$name] ?? $paths['cube'];
    return '<svg class="nav-ico" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">' . $d . '</svg>';
};

$link = static function (string $href, string $label, string $icon = 'cube') use ($path, $ico): string {
    if ($href === '/admin') {
        $active = $path === '/admin';
    } elseif ($href === '/admin/payments') {
        $active = $path === '/admin/payments' || (str_starts_with($path, '/admin/payments/') && !str_starts_with($path, '/admin/payments/gateways'));
    } else {
        $active = str_starts_with($path, $href);
    }
    return '<a href="' . e($href) . '" class="' . ($active ? 'is-active' : '') . '" data-nav-label="' . e(mb_strtolower($label)) . '">'
        . $ico($icon) . '<span>' . e($label) . '</span></a>';
};

$crumbs = $breadcrumbs ?? null;
if (!is_array($crumbs)) {
    $crumbs = [['label' => 'Админ', 'href' => '/admin']];
    if (($title ?? '') !== '' && ($title ?? '') !== 'Дашборд' && $path !== '/admin') {
        $crumbs[] = ['label' => (string) $title];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Админ') ?> — WL Sales Admin</title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/panel.css?v=2">
</head>
<body class="panel-body panel-admin">
<div class="panel-sidebar-backdrop" data-sidebar-close></div>
<div class="panel-shell">
  <aside class="panel-sidebar" id="admin-sidebar">
    <a class="panel-brand" href="/admin">
      <span class="dot">WL</span>
      <span>WL Sales Admin<span class="brand-sub">Admin panel</span></span>
    </a>

    <div class="panel-nav-search" data-admin-nav-search>
      <svg class="ico" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.473 9.8l3.613 3.614a.75.75 0 1 0 1.06-1.06l-3.614-3.613A5.5 5.5 0 0 0 9 3.5ZM5.5 9a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0Z" clip-rule="evenodd"/></svg>
      <input type="search" placeholder="Поиск в меню…" autocomplete="off" aria-label="Поиск в меню">
      <button type="button" class="clear" aria-label="Очистить" title="Очистить">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
      </button>
    </div>
    <p class="panel-nav-search-empty" data-nav-empty hidden>Ничего не найдено</p>

    <nav class="panel-nav" data-admin-nav>
      <div class="nav-group" data-nav-group="Обзор">
        <div class="nav-section">Обзор</div>
        <?= $link('/admin', 'Дашборд', 'home') ?>
      </div>
      <div class="nav-group" data-nav-group="Биллинг">
        <div class="nav-section">Биллинг</div>
        <?= $link('/admin/products', 'Товары', 'cube') ?>
        <?= $link('/admin/orders', 'Заказы', 'cart') ?>
        <?= $link('/admin/payments', 'Платежи', 'card') ?>
        <?= $link('/admin/payments/gateways', 'Платёжные шлюзы', 'bank') ?>
      </div>
      <div class="nav-group" data-nav-group="Пользователи">
        <div class="nav-section">Пользователи</div>
        <?= $link('/admin/users', 'Пользователи', 'users') ?>
        <?= $link('/admin/tickets', 'Тикеты', 'chat') ?>
      </div>
      <div class="nav-group" data-nav-group="Инфраструктура">
        <div class="nav-section">Инфраструктура</div>
        <?= $link('/admin/servers', 'Инвентарь', 'server') ?>
        <?= $link('/admin/proxy', 'Белый вход', 'globe') ?>
        <?= $link('/admin/mail', 'Почта (SMTP)', 'mail') ?>
      </div>
      <div class="nav-group" data-nav-group="Система">
        <div class="nav-section">Система</div>
        <?= $link('/admin/settings', 'Настройки', 'cog') ?>
      </div>
    </nav>

    <div class="panel-sidebar-foot">
      <a class="panel-footer-link" href="/">← На сайт</a>
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
            <?php foreach ($crumbs as $i => $c): ?>
              <?php if ($i > 0): ?><span class="sep">/</span><?php endif; ?>
              <?php if (!empty($c['href']) && $i < count($crumbs) - 1): ?>
                <a href="<?= e($c['href']) ?>"><?= e($c['label']) ?></a>
              <?php else: ?>
                <span class="<?= $i === count($crumbs) - 1 ? 'cur' : '' ?>"><?= e($c['label']) ?></span>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <h1><?= e($title ?? 'Админ') ?></h1>
        </div>
      </div>
      <div class="panel-user" data-user-menu>
        <button type="button" class="panel-user-btn" data-user-toggle>
          <span class="panel-user-avatar"><?= e($initials) ?></span>
          <span class="meta"><?= e($email) ?></span>
        </button>
        <div class="panel-user-menu">
          <a href="/account">Личный кабинет</a>
          <a href="/">Сайт</a>
          <form action="/logout" method="post">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit">Выйти</button>
          </form>
        </div>
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
</body>
</html>
