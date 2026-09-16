# WL Sales

Магазин VPS / выделенных серверов с публичными («белыми») IP: аренда, рассрочка и выкуп.

Продакшен: https://wlsales.1tlt.ru

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
    ServerName wlsales.1tlt.ru
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

- Email: `admin@wlsales.1tlt.ru`
- Пароль: `ChangeMeAdmin2026!` (из `.env.example`)

**Смените пароль сразу после первого входа.**

## Cron

Истечение неоплаченных заказов через 48 часов:

```cron
*/30 * * * * /usr/bin/php /var/www/wlsales/public/index.php cron >> /var/www/wlsales/storage/logs/cron.log 2>&1
```

Либо HTTP (токен = `APP_KEY` из `.env`):

```
GET https://wlsales.1tlt.ru/cron?token=YOUR_APP_KEY
```

## Оплата

### Ручной СБП

В админке → Настройки укажите телефон СБП. Клиент выбирает способ, переводит, жмёт «Я оплатил». Админ подтверждает в «Платежи».

### Crypto USDT TRC20

Укажите адрес в настройках (не оставляйте заглушку `TXXX...`). Подтверждение вручную.

### ЮKassa

1. В `.env` заполните:

```
YOOKASSA_SHOP_ID=...
YOOKASSA_SECRET_KEY=...
YOOKASSA_RETURN_URL=https://wlsales.1tlt.ru/account/orders
```

2. В кабинете ЮKassa укажите URL вебхука:

```
https://wlsales.1tlt.ru/webhooks/yookassa
```

3. Событие: `payment.succeeded`. Сумма сверяется с заказом; повторная обработка идемпотентна.

Пока ключи пустые — способ скрыт на витрине.

## Почта

По умолчанию `MAIL_DRIVER=mail` (нативный `mail()`).

Для PHPMailer:

```bash
composer require phpmailer/phpmailer
```

и в `.env`: `MAIL_DRIVER=phpmailer` + SMTP-поля.

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
sql/             schema.sql, seed.sql
```

## Разработка без Apache

```bash
cd public && php -S 127.0.0.1:8080
```

Убедитесь, что `.env` указывает на рабочую БД.
