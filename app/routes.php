<?php

declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\OrdersController;
use App\Controllers\Admin\PaymentsController;
use App\Controllers\Admin\ProductsController;
use App\Controllers\Admin\ServersController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\TicketsController;
use App\Controllers\Admin\UsersController;
use App\Controllers\AuthController;
use App\Controllers\CatalogController;
use App\Controllers\CheckoutController;
use App\Controllers\CronController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\WebhookController;
use App\Core\Router;

$router = new Router();

// Public
$router->get('/', [HomeController::class, 'index']);
$router->get('/catalog', [CatalogController::class, 'index']);
$router->get('/catalog/{slug}', [CatalogController::class, 'show']);
$router->get('/how', [PageController::class, 'how']);
$router->get('/faq', [PageController::class, 'faq']);
$router->get('/contacts', [PageController::class, 'contacts']);
$router->get('/legal/offer', [PageController::class, 'offer']);
$router->get('/legal/privacy', [PageController::class, 'privacy']);

// Auth
$router->get('/login', [AuthController::class, 'loginForm'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->get('/register', [AuthController::class, 'registerForm'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout']);

// Account
$router->get('/account', [AccountController::class, 'index'], ['auth']);
$router->get('/account/orders', [AccountController::class, 'orders'], ['auth']);
$router->get('/account/orders/{id}', [AccountController::class, 'orderShow'], ['auth']);
$router->get('/account/servers', [AccountController::class, 'servers'], ['auth']);
$router->get('/account/tickets', [AccountController::class, 'tickets'], ['auth']);
$router->post('/account/tickets', [AccountController::class, 'ticketCreate'], ['auth']);
$router->get('/account/tickets/{id}', [AccountController::class, 'ticketShow'], ['auth']);
$router->post('/account/tickets/{id}', [AccountController::class, 'ticketReply'], ['auth']);
$router->get('/account/password', [AccountController::class, 'passwordForm'], ['auth']);
$router->post('/account/password', [AccountController::class, 'passwordUpdate'], ['auth']);
$router->post('/account/profile', [AccountController::class, 'profileUpdate'], ['auth']);

// Checkout / pay
$router->post('/checkout/{productId}', [CheckoutController::class, 'checkout'], ['auth']);
$router->get('/pay/{orderId}', [CheckoutController::class, 'pay'], ['auth']);
$router->post('/pay/{orderId}', [CheckoutController::class, 'startPayment'], ['auth']);
$router->post('/pay/{orderId}/confirm', [CheckoutController::class, 'markPaid'], ['auth']);
$router->post('/webhooks/yookassa', [WebhookController::class, 'yookassa']);

// Admin
$router->get('/admin', [DashboardController::class, 'index'], ['admin']);
$router->get('/admin/products', [ProductsController::class, 'index'], ['admin']);
$router->get('/admin/products/create', [ProductsController::class, 'createForm'], ['admin']);
$router->post('/admin/products/create', [ProductsController::class, 'create'], ['admin']);
$router->get('/admin/products/{id}/edit', [ProductsController::class, 'editForm'], ['admin']);
$router->post('/admin/products/{id}/edit', [ProductsController::class, 'update'], ['admin']);
$router->post('/admin/products/{id}/delete', [ProductsController::class, 'delete'], ['admin']);

$router->get('/admin/orders', [OrdersController::class, 'index'], ['admin']);
$router->get('/admin/orders/{id}', [OrdersController::class, 'show'], ['admin']);
$router->post('/admin/orders/{id}/status', [OrdersController::class, 'updateStatus'], ['admin']);
$router->post('/admin/orders/{id}/deliver', [OrdersController::class, 'deliver'], ['admin']);

$router->get('/admin/payments', [PaymentsController::class, 'index'], ['admin']);
$router->post('/admin/payments/{id}/confirm', [PaymentsController::class, 'confirm'], ['admin']);

$router->get('/admin/users', [UsersController::class, 'index'], ['admin']);
$router->post('/admin/users/{id}', [UsersController::class, 'update'], ['admin']);

$router->get('/admin/servers', [ServersController::class, 'index'], ['admin']);
$router->post('/admin/servers', [ServersController::class, 'create'], ['admin']);
$router->post('/admin/servers/{id}', [ServersController::class, 'update'], ['admin']);
$router->post('/admin/servers/{id}/delete', [ServersController::class, 'delete'], ['admin']);

$router->get('/admin/tickets', [TicketsController::class, 'index'], ['admin']);
$router->get('/admin/tickets/{id}', [TicketsController::class, 'show'], ['admin']);
$router->post('/admin/tickets/{id}', [TicketsController::class, 'reply'], ['admin']);

$router->get('/admin/settings', [SettingsController::class, 'index'], ['admin']);
$router->post('/admin/settings', [SettingsController::class, 'save'], ['admin']);

$router->get('/cron', [CronController::class, 'run']);

return $router;
