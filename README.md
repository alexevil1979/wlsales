# WL Sales

Магазин VPS / выделенных серверов с публичными («белыми») IP: аренда, рассрочка и выкуп.

Продакшен: https://white-list.space  
Код на сервере: `/ssd/www/wlsales`

## Обновление на VPS

```bash
cd /ssd/www/wlsales
git pull origin main
# при новой SQL-миграции:
# mysql -u root -p wlsales < sql/00X_....sql
sudo systemctl reload php8.2-fpm
```

После pull: новые разделы админки — **Платёжные системы** (`/admin/payments/gateways`), **Почта SMTP** (`/admin/mail`).

## Стек

- PHP 8.2 (без Laravel/Symfony)
- Apache + `mod_rewrite`, DocumentRoot = `public/`
- MySQL 5.7+ (utf8mb4, InnoDB)
- Сессии PHP + `password_hash`
- PDO prepared statements

## Быстрый старт

1. Клонируйте репозиторий на сервер.
2. Скопируйте окружение:

```bash
cp .env.example .env
# отредактируйте DB_*, APP_URL, APP_KEY, ENCRYPT_KEY, ADMIN_*
```

3. Создайте БД и импортируйте схему:

```bash
mysql -u root -p -e "CREATE DATABASE wlsales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p wlsales < sql/schema.sql
mysql -u root -p wlsales < sql/seed.sql
mysql -u root -p wlsales < sql/002_proxy.sql
```

4. Права на запись:

```bash
chmod -R 775 storage public/uploads
chown -R www-data:www-data storage public/uploads
```

5. DocumentRoot должен указывать на `public/`, `AllowOverride All`.

### Пример VirtualHost

```apache
<VirtualHost *:80>
    ServerName white-list.space
    ServerAlias www.white-list.space
    DocumentRoot /var/www/wlsales/public

    <Directory /var/www/wlsales/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/wlsales-error.log
    CustomLog ${APACHE_LOG_DIR}/wlsales-access.log combined
</VirtualHost>
```

После выпуска сертификата добавьте SSL (certbot) и редирект на HTTPS.

## Админ по умолчанию (seed)

- Email: `admin@white-list.space`
- Пароль: `ChangeMeAdmin2026!` (из `.env.example`)

**Смените пароль сразу после первого входа.**

## Cron

Истечение неоплаченных заказов через 48 часов:

```cron
*/30 * * * * /usr/bin/php /var/www/wlsales/public/index.php cron >> /var/www/wlsales/storage/logs/cron.log 2>&1
```

Либо HTTP (токен = `APP_KEY` из `.env`):

```
GET https://white-list.space/cron?token=YOUR_APP_KEY
```

## Оплата

### Ручной СБП / USDT / шлюзы
Админка → **Платёжные шлюзы** (`/admin/payments/gateways`) — как Filament `PaymentGateway` в fullvpnservice:
список записей (код, вкл/выкл, тест, мин. сумма), редактирование `config` по типам (ЮKassa, FreeKassa×3, Platega, NOWPayments, Overpay, FriendlyPay, Exnode, Pally, 1Plat, BetaTransfer и др.).

Пошагово ЮKassa с момента регистрации аккаунта: [docs/yookassa-setup.md](docs/yookassa-setup.md) (shopId / secretKey / webhook → куда вставлять в админку; опционально SMTP Яндекса).

Миграция на сервере:

```bash
mysql -u root -p wlsales < sql/003_payment_gateways.sql
```

Ключи из старых `settings` / `.env` подтянутся автоматически при первом открытии раздела.

Webhooks:
- `POST /webhooks/yookassa`
- `POST /webhooks/freekassa`
- `POST /webhooks/platega`
- `POST /webhooks/nowpayments`

FreeKassa даёт три метода: СБП (i=44), карты РФ (i=36), world VISA USD (i=32).

### Telegram-уведомления админу
`TELEGRAM_BOT_TOKEN` + `TELEGRAM_ADMIN_CHAT_ID` в `.env` или в Настройках.
События: регистрация, оплата, выдача, тикеты. Кнопка «Тест Telegram» в админке.

## Почта

Админка → **Почта (SMTP)** (`/admin/mail`) — как SmtpSetting в fullvpnservice:
host/port/TLS/login/from, тест письма, флаги писем по тикетам.
Значения из админки важнее `.env`.

Без PHPMailer работает встроенный SMTP-клиент. Опционально:

```bash
composer require phpmailer/phpmailer
```

`.env` (fallback): `MAIL_FROM`, `MAIL_SMTP_HOST`, `MAIL_SMTP_PORT`, `MAIL_SMTP_USER`, `MAIL_SMTP_PASS`, `MAIL_SMTP_SECURE`.

### Тикеты

ЛК `/account/tickets` ↔ админка `/admin/tickets`.
При создании тикета: Telegram админу + письмо клиенту.
При ответе поддержки: письмо клиенту со ссылкой в ЛК.

## Белый вход для сайта (proxy)

Вторая линейка: клиент ставит A-запись на наш публичный IP, мы поднимаем HTTPS + reverse proxy на его origin.

- Витрина: `/proxy`
- Кабинет: `/account/proxy`
- Админка: `/admin/proxy` (ноды, подписки, очередь DNS, nginx-сниппет)

### Миграция на уже развёрнутой БД

```bash
mysql -u root -p wlsales < sql/002_proxy.sql
```

### Как админу поднять домен на ноде

1. Назначить подписке ноду в `/admin/proxy`.
2. Клиент ставит A `@` и `www` на `node.public_ip` (Cloudflare — серое облако).
3. «Проверить DNS» в очереди — A должен совпасть с IP ноды.
4. «Сниппет» → скопировать nginx `server{}` на ноду (обычно `/etc/nginx/sites-available/`).
5. На ноде: `nginx -t && systemctl reload nginx`
6. `certbot --nginx -d example.ru -d www.example.ru`
7. В админке: `ssl_status=issued`, при необходимости `status=active`, галка origin ок.

Лимит доменов на ноду — `domains_cap`. Не выдавайте тот же сервер как dedicated VPS, если на ноде уже есть чужие `proxy_domains` (проверка при выдаче заказа).

Продление: cron раз в день — за 3 дня письмо, в день `period_end` → `suspended`.

## Безопасность

- CSRF на всех POST
- Rate limit логина (таблица `login_attempts`)
- `/admin` только для `role=admin`
- `.env` вне webroot
- `uploads/` без исполнения PHP
- Заголовки `X-Frame-Options`, `X-Content-Type-Options`
- Секреты серверов хранятся AES-256-CBC (`ENCRYPT_KEY`), в кабинете — «Показать»

## Структура

```
public/          index.php, assets, uploads
app/Core/        Router, Database, Auth, View, Csrf, Validator
app/Controllers/ публичные + Admin/*
app/Models/      User, Product, Order, Payment, Server, Ticket…
app/Services/    PaymentGateway (ManualSbp, YooKassa, CryptoStub)
views/           layouts + страницы
sql/             schema.sql, seed.sql, 002_proxy.sql
```

## Разработка без Apache

```bash
cd public && php -S 127.0.0.1:8080
```

Убедитесь, что `.env` указывает на рабочую БД.
