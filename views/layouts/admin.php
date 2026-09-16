<?php
/** @var string $content */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$link = static function (string $href, string $label) use ($path): string {
    $active = $href === '/admin' ? ($path === '/admin') : str_starts_with($path, $href);
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
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="admin-body">
<div class="admin-shell">
  <aside class="admin-nav">
    <a class="logo" href="/admin"><span></span>WL Admin</a>
    <?= $link('/admin', 'Дашборд') ?>
    <?= $link('/admin/products', 'Товары') ?>
    <?= $link('/admin/orders', 'Заказы') ?>
    <?= $link('/admin/payments', 'Платежи') ?>
    <?= $link('/admin/servers', 'Инвентарь') ?>
    <?= $link('/admin/users', 'Пользователи') ?>
    <?= $link('/admin/tickets', 'Тикеты') ?>
    <?= $link('/admin/settings', 'Настройки') ?>
    <a href="/" style="margin-top:1rem">← На сайт</a>
  </aside>
  <div class="admin-main">
    <h1 style="margin-top:0"><?= e($title ?? 'Админ') ?></h1>
    <?php if ($m = flash('success')): ?><div class="flash flash-success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash('error')): ?><div class="flash flash-error"><?= e($m) ?></div><?php endif; ?>
    <?= $content ?>
  </div>
</div>
</body>
</html>
