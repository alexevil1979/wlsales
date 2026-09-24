# Перенос WL Sales на новый VPS (один в один)

Цель: новый сервер работает как старый — те же пользователи, заказы, платежи, секреты серверов, загрузки, SSL, cron, домен `white-list.space`.

**Не делайте** «чистую установку» из `sql/schema.sql` + `seed.sql` на проде: это сотрёт/заменит данные. Нужен **полный дамп** БД и копии файлов вне git.

Продакшен-путь на текущем сервере: `/ssd/www/wlsales`  
DocumentRoot: `public/`  
Стек: PHP 8.2-FPM + Apache (`mod_rewrite`) + MySQL (utf8mb4) + Let’s Encrypt.

---

## Что обязательно перенести

| Что | Где на старом | Зачем |
|---|---|---|
| Код | `/ssd/www/wlsales` (git) | приложение |
| `.env` | `/ssd/www/wlsales/.env` | БД, `APP_KEY`, **`ENCRYPT_KEY`**, Telegram, fallback SMTP/платежи |
| БД `wlsales` | MySQL | пользователи, заказы, платежи, шлюзы, SMTP из админки, proxy |
| Загрузки | `public/uploads/` | файлы вне git |
| Apache vhost + SSL | `/etc/apache2/...`, `/etc/letsencrypt/` | сайт и сертификаты |
| Cron | `crontab` пользователя root/`www-data` | истечение заказов, proxy |
| (опц.) логи | `storage/logs/` | разбор инцидентов |

Критично сохранить **без изменений**:

- `ENCRYPT_KEY` — иначе не расшифруются пароли/секреты выданных серверов в админке и ЛК.
- `APP_KEY` — токен HTTP-cron `/cron?token=...`.
- Полный дамп MySQL (не только схему).

Настройки SMTP, платёжных шлюзов, Telegram часто лежат **в БД** (админка). Их переносит дамп; `.env` — fallback и ключи шифрования.

Proxy-**ноды** (отдельные машины с nginx для клиентских доменов) — это не этот VPS. Их IP в таблице `proxy_nodes` не меняются сами по себе. Меняется только IP **магазина** (`white-list.space`), если DNS указывает на новый VPS.

---

## Чеклист переноса (кратко)

1. На старом: дамп БД + архив `.env` + `uploads` (+ при желании vhost/certs/cron).
2. На новом: ОС, PHP 8.2, Apache, MySQL, сертификаты.
3. Клон репо в `/ssd/www/wlsales`, положить `.env` и `uploads`.
4. Создать БД и пользователя, импортировать дамп.
5. Права, VirtualHost, HTTPS, cron.
6. DNS A/AAAA на новый IP.
7. Проверки + отключение старого.

Окно простоя: обычно от смены DNS до прогрева TTL. Можно сначала поднять сайт на новом IP, проверить по `/etc/hosts`, потом переключить DNS.

---

## 0. Подготовка (оба сервера)

- Доступ SSH root/sudo на старый и новый VPS.
- Зафиксируйте IP нового сервера.
- TTL DNS для `white-list.space` / `www` заранее снизьте (300–600 с), если возможно.
- На старом временно включите режим обслуживания (опционально), чтобы не писать заказы во время дампа.

Имена ниже:

- **OLD** — текущий прод (`/ssd/www/wlsales`)
- **NEW** — новый VPS
- Домен: `white-list.space`

---

## 1. Снимаем бэкап на OLD

Выполнять на **старом** сервере.

### 1.1. Дамп базы (обязательно)

```bash
sudo mkdir -p /root/wlsales-migrate
sudo mysqldump -u root -p \
  --single-transaction --routines --triggers --events \
  --default-character-set=utf8mb4 \
  wlsales | gzip > /root/wlsales-migrate/wlsales-$(date +%F).sql.gz
```

Проверка размера:

```bash
ls -lh /root/wlsales-migrate/
zcat /root/wlsales-migrate/wlsales-*.sql.gz | head -n 40
```

Должны быть `CREATE TABLE`, `INSERT` с реальными данными — не пустая схема.

### 1.2. Секреты и uploads

```bash
sudo tar -czf /root/wlsales-migrate/secrets-uploads.tar.gz \
  -C /ssd/www/wlsales \
  .env \
  public/uploads
```

`.env` **не** коммитьте в git. Храните архив только на серверах / в защищённом хранилище.

### 1.3. Конфиг Apache, SSL, cron (рекомендуется)

```bash
# VirtualHost (путь может отличаться — найдите)
sudo grep -R "white-list.space\|wlsales" /etc/apache2/sites-enabled/ /etc/apache2/sites-available/ 2>/dev/null

# Скопируйте найденные файлы, например:
sudo cp /etc/apache2/sites-available/*wlsales* /root/wlsales-migrate/ 2>/dev/null || true
sudo cp /etc/apache2/sites-available/*white-list* /root/wlsales-migrate/ 2>/dev/null || true

# Let’s Encrypt (если certbot уже выдавал серты на этом хосте)
sudo tar -czf /root/wlsales-migrate/letsencrypt.tar.gz -C / etc/letsencrypt 2>/dev/null || true

# Cron
crontab -l > /root/wlsales-migrate/crontab-root.txt 2>/dev/null || true
sudo crontab -u www-data -l > /root/wlsales-migrate/crontab-www-data.txt 2>/dev/null || true
```

### 1.4. Состав PHP-модулей (для совпадения)

```bash
php -v
php -m
apache2ctl -M 2>/dev/null | sort
dpkg -l | grep -E 'php8\.2|apache2|mysql|mariadb|certbot' | awk '{print $2, $3}'
```

Сохраните вывод в `/root/wlsales-migrate/packages.txt`.

### 1.5. Копия бэкапа на NEW

С **вашей машины** или с OLD:

```bash
# пример: с локального ПК через scp (подставьте IP/user)
scp -r root@OLD_IP:/root/wlsales-migrate ./wlsales-migrate
scp -r ./wlsales-migrate root@NEW_IP:/root/
```

Или напрямую OLD → NEW:

```bash
scp -r /root/wlsales-migrate root@NEW_IP:/root/
```

---

## 2. Базовая ОС на NEW

Ubuntu 22.04/24.04 (или совместимый Debian). Команды для Ubuntu.

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y \
  apache2 \
  mysql-server \
  certbot python3-certbot-apache \
  git curl unzip \
  php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml php8.2-mbstring \
  php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath php8.2-opcache
```

Если пакет `php8.2` не находится — добавьте [ondrej/php](https://launchpad.net/~ondrej/+archive/ubuntu/php) или поставьте ту же мажорную версию PHP, что на OLD (`php -v`).

Включите Apache-модули:

```bash
sudo a2enmod rewrite proxy_fcgi setenvif ssl headers
sudo a2enconf php8.2-fpm
sudo systemctl enable --now apache2 php8.2-fpm mysql
```

Создайте каталог как на проде:

```bash
sudo mkdir -p /ssd/www
sudo chown "$USER":"$USER" /ssd/www
```

(Если пользователь деплоя другой — позже выставьте `www-data` на writable-каталоги.)

---

## 3. Код приложения на NEW

### 3.1. Клон из git (предпочтительно)

```bash
cd /ssd/www
git clone git@github.com:YOUR_ORG/wlsales.git wlsales
# или HTTPS-URL вашего remote
cd /ssd/www/wlsales
git checkout main
git pull origin main
```

Путь **должен** быть `/ssd/www/wlsales`, чтобы cron, доки и привычки совпадали. Если меняете путь — поправьте DocumentRoot и crontab.

### 3.2. Вернуть `.env` и uploads

```bash
cd /ssd/www/wlsales
sudo tar -xzf /root/wlsales-migrate/secrets-uploads.tar.gz -C /ssd/www/wlsales
# убедитесь:
ls -la .env public/uploads
```

Проверьте в `.env` (значения **как на OLD**, не из `.env.example`):

- `APP_URL=https://white-list.space`
- `APP_ENV=production`, `APP_DEBUG=0`
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` — под пользователя, которого создадите на NEW
- **`APP_KEY`** и **`ENCRYPT_KEY`** — байт-в-байт со старого

Если MySQL на том же хосте: `DB_HOST=127.0.0.1`.

Composer в проекте не обязателен (PHPMailer опционален). Если на OLD был `vendor/` с PHPMailer:

```bash
# только если нужен phpmailer
cd /ssd/www/wlsales
composer require phpmailer/phpmailer
```

---

## 4. MySQL: пользователь и импорт дампа

### 4.1. База и пользователь

Подставьте пароль из старого `.env` (`DB_PASS`) — так проще не менять `.env`.

```bash
sudo mysql -e "
CREATE DATABASE IF NOT EXISTS wlsales
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'wlsales'@'localhost' IDENTIFIED BY 'ПАРОЛЬ_ИЗ_ENV';
GRANT ALL PRIVILEGES ON wlsales.* TO 'wlsales'@'localhost';
FLUSH PRIVILEGES;
"
```

Если пользователь уже был с другим паролем:

```bash
sudo mysql -e "ALTER USER 'wlsales'@'localhost' IDENTIFIED BY 'ПАРОЛЬ_ИЗ_ENV'; FLUSH PRIVILEGES;"
```

### 4.2. Импорт дампа

```bash
zcat /root/wlsales-migrate/wlsales-*.sql.gz | sudo mysql -u root -p wlsales
```

**Не** запускайте после этого `sql/seed.sql` и не накатывайте `schema.sql` поверх дампа.

Миграции `sql/002_*.sql` / `003_*.sql` нужны только если дамп со **старой** схемы без этих таблиц. На актуальном проде они уже в дампе — повторный импорт может упасть с «table exists».

Проверка:

```bash
mysql -u wlsales -p wlsales -e "SHOW TABLES; SELECT COUNT(*) AS users FROM users; SELECT COUNT(*) AS orders FROM orders;"
```

---

## 5. Права на запись

```bash
cd /ssd/www/wlsales
sudo mkdir -p storage/logs storage/rate_limit public/uploads
sudo chown -R www-data:www-data storage public/uploads
sudo chmod -R 775 storage public/uploads
# код может оставаться у пользователя деплоя; важно, чтобы Apache/php-fpm читал файлы:
sudo chown -R "$USER":www-data /ssd/www/wlsales
sudo find /ssd/www/wlsales -type d -exec chmod 755 {} \;
sudo find /ssd/www/wlsales -type f -exec chmod 644 {} \;
sudo chmod -R 775 storage public/uploads
```

---

## 6. Apache VirtualHost

Создайте сайт (подставьте PHP-сокет при необходимости):

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

Если на OLD использовался php-fpm через отдельный `<FilesMatch>` / proxy — скопируйте vhost из бэкапа `/root/wlsales-migrate/` один в один и поправьте только пути, если нужно.

### HTTPS

Пока DNS ещё на OLD, можно:

**Вариант A** — временный hosts на вашей машине → `certbot` с `--webroot` после смены DNS.

**Вариант B** — после переключения DNS:

```bash
sudo certbot --apache -d white-list.space -d www.white-list.space
```

**Вариант C** — перенос сертификатов с OLD (`letsencrypt.tar.gz`):

```bash
sudo tar -xzf /root/wlsales-migrate/letsencrypt.tar.gz -C /
sudo certbot renew --dry-run
# подключите SSL-vhost как на OLD или снова: certbot --apache ...
```

После выпуска SSL: редирект HTTP→HTTPS (certbot обычно делает сам).

---

## 7. Cron (обязательно)

На OLD путь мог быть `/ssd/www/wlsales` или `/var/www/wlsales`. На NEW используйте реальный путь:

```bash
sudo crontab -u www-data -e
```

Добавьте (или перенесите из `crontab-*.txt`):

```cron
*/30 * * * * /usr/bin/php /ssd/www/wlsales/public/index.php cron >> /ssd/www/wlsales/storage/logs/cron.log 2>&1
```

Проверка вручную:

```bash
sudo -u www-data /usr/bin/php /ssd/www/wlsales/public/index.php cron
tail -n 50 /ssd/www/wlsales/storage/logs/cron.log
```

HTTP-вариант (если использовали внешний ping):

```
GET https://white-list.space/cron?token=<APP_KEY из .env>
```

---

## 8. DNS

В панели домена:

| Имя | Тип | Значение |
|---|---|---|
| `@` / `white-list.space` | A | **IP NEW** |
| `www` | A или CNAME | IP NEW или `@` |

AAAA (IPv6) — либо новый IPv6, либо удалите старые записи, чтобы не уезжать на OLD.

Проверка с вашей машины:

```bash
dig +short white-list.space A
curl -I https://white-list.space
```

Пока TTL не истёк, часть клиентов может ходить на OLD — не выключайте OLD сразу (см. §10).

---

## 9. Проверки после переноса

1. Главная `https://white-list.space` открывается, HTTPS валиден.
2. Вход в админку `/admin` существующим админом (из дампа, не «дефолт из seed», если пароль уже меняли).
3. ЛК клиента, заказы, список серверов; кнопка «Показать» секрет — если `ENCRYPT_KEY` верный, секреты читаются.
4. `/admin/payments/gateways` — шлюзы на месте.
5. `/admin/mail` — тест письма.
6. Настройки → тест Telegram (если был).
7. Загрузки: страница, где нужны файлы из `public/uploads`.
8. Webhook ЮKassa и др. (URL тот же домен — менять в кабинетах шлюзов не нужно, если домен не менялся).
9. Cron: запись в `storage/logs/cron.log`.
10. `sudo systemctl status apache2 php8.2-fpm mysql` — active.

Логи при ошибках:

```bash
sudo tail -n 100 /var/log/apache2/wlsales-error.log
tail -n 100 /ssd/www/wlsales/storage/logs/app.log
```

---

## 10. Отключение OLD

Когда DNS стабильно указывает на NEW и прошли проверки:

1. На OLD остановите cron сайта (закомментируйте строки crontab).
2. Остановите Apache/php или весь хост — чтобы не было двойной записи в разные БД.
3. Финальный дамп OLD на всякий случай (храните 2–4 недели).
4. Не удаляйте бэкапы `/root/wlsales-migrate` сразу.

Если после cutover нашли рассинхрон (заказы на OLD за время TTL) — однократно экспортируйте дельту или повторите полный дамп→импорт в окно обслуживания.

---

## 11. Proxy-ноды и платежи (не забыть)

- **Магазин** сменил IP → клиентские A-записи на **ноды proxy не трогаем**, если IP нод те же.
- Если в админке в настройках/документации где-то светился старый IP магазина — обновите текст.
- Кабинеты ЮKassa / FreeKassa / …: URL webhook’ов с тем же доменом менять не нужно.
- Firewall NEW: открыты 80, 443, 22 (SSH). MySQL снаружи не открывать.

---

## 12. Обычные обновления кода после переноса

Как и раньше:

```bash
cd /ssd/www/wlsales
git pull origin main
# только при новой SQL-миграции в релизе:
# mysql -u wlsales -p wlsales < sql/00X_....sql
sudo systemctl reload php8.2-fpm
```

`.env` и `public/uploads` git не трогает — они остаются на диске.

---

## Частые ошибки

| Симптом | Причина | Что сделать |
|---|---|---|
| Секреты серверов «битые» | другой `ENCRYPT_KEY` | вернуть ключ из старого `.env` |
| 500 / пустая страница | нет прав на `storage`, нет `.env`, PHP-модуль | логи Apache, `php -m`, права |
| 404 на ЧПУ | нет `AllowOverride` / `mod_rewrite` | `a2enmod rewrite`, Directory как выше |
| Пустая админка / дефолт-админ | импортировали seed вместо дампа | восстановить из `wlsales-*.sql.gz` |
| Письма/шлюзы пропали | смотрели только `.env`, а данные в БД | проверить дамп / админку |
| Cron не гасит заказы | нет crontab или другой путь PHP | §7 |
| Часть трафика на OLD | TTL / старый AAAA | поправить DNS, подождать |

---

## Минимальный набор файлов для отката

Храните до стабилизации NEW:

- `wlsales-YYYY-MM-DD.sql.gz`
- `secrets-uploads.tar.gz` (`.env` + uploads)
- копии vhost / `letsencrypt` / crontab

С ними можно поднять копию снова на любом VPS по этому же документу.
