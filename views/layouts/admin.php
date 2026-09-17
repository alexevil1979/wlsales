<?php
/** @var string $content */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$user = \App\Core\Auth::user();
$link = static function (string $href, string $label) use ($path): string {
    if ($href === '/admin') {
        $active = $path === '/admin';
    } elseif ($href === '/admin/payments') {
        $active = $path === '/admin/payments' || (str_starts_with($path, '/admin/payments/') && !str_starts_with($path, '/admin/payments/gateways'));
    } else {
        $active = str_starts_with($path, $href);
    }
    return '<a href="' . e($href) . '" class="' . ($active ? 'is-active' : '') . '">' . e($label) . '</a>';
};
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Админ') ?> — WL Sales</title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/panel.css">
</head>
<body class="panel-body panel-admin">
<div class="panel-shell">
  <aside class="panel-sidebar">
    <a class="panel-brand" href="/admin"><span class="dot">WL</span> WL Admin</a>
    <nav class="panel-nav">
      <div class="nav-section">Обзор</div>
      <?= $link('/admin', 'Дашборд') ?>
      <div class="nav-section">Продажи</div>
      <?= $link('/admin/products', 'Товары') ?>
      <?= $link('/admin/orders', 'Заказы') ?>
      <?= $link('/admin/payments', 'Платежи') ?>
      <?= $link('/admin/proxy', 'Белый вход') ?>
      <div class="nav-section">Инфра</div>
      <?= $link('/admin/servers', 'Инвентарь') ?>
      <?= $link('/admin/users', 'Пользователи') ?>
      <?= $link('/admin/tickets', 'Тикеты') ?>
      <div class="nav-section">Система</div>
      <?= $link('/admin/payments/gateways', 'Платёжные системы') ?>
      <?= $link('/admin/settings', 'Настройки') ?>
    </nav>
    <a class="panel-footer-link" href="/">← На сайт</a>
  </aside>
  <div class="panel-main">
    <header class="panel-topbar">
      <h1><?= e($title ?? 'Админ') ?></h1>
      <div class="meta"><?= e($user['email'] ?? '') ?></div>
    </header>
    <div class="panel-content">
      <?php if ($m = flash('success')): ?><div class="flash flash-success"><?= e($m) ?></div><?php endif; ?>
      <?php if ($m = flash('error')): ?><div class="flash flash-error"><?= e($m) ?></div><?php endif; ?>
      <?= $content ?>
    </div>
  </div>
</div>
</body>
</html>
