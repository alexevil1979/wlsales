# Чистая установка WL Sales на новый VPS

С нуля: пустая БД, seed-админ, без переноса старых заказов/пользователей.

Продакшен-путь: `/ssd/www/wlsales`  
DocumentRoot: `public/`  
Домен (пример): `white-list.space`  
Стек: PHP 8.2-FPM + Apache (`mod_rewrite`) + MySQL + Let’s Encrypt.

---

## 1. Пакеты (Ubuntu)

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y \
  apache2 \
  mysql-server \
  certbot python3-certbot-apache \
  git curl \
  php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml php8.2-mbstring \
  php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath php8.2-opcache

sudo a2enmod rewrite proxy_fcgi setenvif ssl headers
sudo a2enconf php8.2-fpm
sudo systemctl enable --now apache2 php8.2-fpm mysql
```

Если нет `php8.2` — поставьте ту же ветку PHP 8.2 через ondrej/php или используйте пакеты дистрибутива с PHP ≥ 8.2.

---

## 2. Код

```bash
sudo mkdir -p /ssd/www
sudo chown "$USER":"$USER" /ssd/www
cd /ssd/www
git clone https://github.com/alexevil1979/wlsales.git wlsales
cd /ssd/www/wlsales
git checkout main
```

Подставьте свой remote, если репозиторий другой.

---

## 3. `.env`

```bash
cd /ssd/www/wlsales
cp .env.example .env
nano .env
```

Обязательно задайте:

| Ключ | Что поставить |
|---|---|
| `APP_URL` | `https://white-list.space` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `0` |
| `APP_KEY` | длинная случайная строка (≥32 символа) |
| `ENCRYPT_KEY` | другая случайная строка (≥32); **сохраните** — без неё потом не прочитать секреты серверов |
| `DB_*` | хост/имя БД/логин/пароль (см. §4) |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | первый админ из seed |

Остальное (SMTP, платежи, Telegram) можно позже в админке или в `.env`.

Сгенерировать ключи:

```bash
openssl rand -base64 32
# дважды — для APP_KEY и ENCRYPT_KEY
```

---

## 4. MySQL

```bash
sudo mysql
```

В консоли MySQL:

```sql
CREATE DATABASE wlsales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wlsales'@'localhost' IDENTIFIED BY 'СИЛЬНЫЙ_ПАРОЛЬ';
GRANT ALL PRIVILEGES ON wlsales.* TO 'wlsales'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Тот же пароль — в `.env` (`DB_USER=wlsales`, `DB_PASS=...`, `DB_NAME=wlsales`, `DB_HOST=127.0.0.1`).

Импорт схемы и данных по умолчанию:

```bash
cd /ssd/www/wlsales
mysql -u wlsales -p wlsales < sql/schema.sql
mysql -u wlsales -p wlsales < sql/seed.sql
mysql -u wlsales -p wlsales < sql/002_proxy.sql
mysql -u wlsales -p wlsales < sql/003_payment_gateways.sql
```

Админ из seed (если не меняли в `.env` до seed — см. seed/README): после первого входа **смените пароль**.  
Дефолт из `.env.example`: `admin@white-list.space` / `ChangeMeAdmin2026!`.

Если админ не создался или нужен сброс:

```bash
php public/index.php reset-admin
```

(берёт `ADMIN_EMAIL` / `ADMIN_PASSWORD` из `.env`).

---

## 5. Права

```bash
cd /ssd/www/wlsales
mkdir -p storage/logs storage/rate_limit public/uploads
sudo chown -R www-data:www-data storage public/uploads
sudo chmod -R 775 storage public/uploads
```

---

## 6. Apache

```bash
sudo tee /etc/apache2/sites-available/wlsales.conf >/dev/null <<'EOF'
<VirtualHost *:80>
    ServerName white-list.space
    ServerAlias www.white-list.space
    DocumentRoot /ssd/www/wlsales/public

    <Directory /ssd/www/wlsales/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/wlsales-error.log
    CustomLog ${APACHE_LOG_DIR}/wlsales-access.log combined
</VirtualHost>
EOF

sudo a2ensite wlsales.conf
sudo a2dissite 000-default.conf 2>/dev/null || true
sudo apache2ctl configtest
sudo systemctl reload apache2
```

DNS: A-записи `@` и `www` → IP этого VPS.

HTTPS:

```bash
sudo certbot --apache -d white-list.space -d www.white-list.space
```

---

## 7. Cron

```bash
sudo crontab -u www-data -e
```

Строка:

```cron
*/30 * * * * /usr/bin/php /ssd/www/wlsales/public/index.php cron >> /ssd/www/wlsales/storage/logs/cron.log 2>&1
```

Проверка:

```bash
sudo -u www-data /usr/bin/php /ssd/www/wlsales/public/index.php cron
```

---

## 8. После установки (админка)

1. Войти в `/admin`, сменить пароль админа.
2. **Почта SMTP** — `/admin/mail`.
3. **Платёжные системы** — `/admin/payments/gateways` (ЮKassa: [docs/yookassa-setup.md](yookassa-setup.md)).
4. **Настройки** / Telegram — токен и chat id.
5. Товары, proxy-ноды — по необходимости.

Firewall: открыты 22, 80, 443; MySQL снаружи не слушать.

---

## 9. Обновления кода

```bash
cd /ssd/www/wlsales
git pull origin main
# если в релизе новая SQL-миграция:
# mysql -u wlsales -p wlsales < sql/00X_....sql
sudo systemctl reload php8.2-fpm
```

---

## Краткий чеклист

1. PHP 8.2 + Apache + MySQL + certbot  
2. `git clone` → `/ssd/www/wlsales`  
3. `.env` из example (ключи, БД, URL)  
4. БД + `schema` + `seed` + `002` + `003`  
5. Права на `storage` и `uploads`  
6. VirtualHost → DocumentRoot `public/`  
7. DNS + certbot  
8. Cron  
9. Админка: пароль, почта, платежи  
