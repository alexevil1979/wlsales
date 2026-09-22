-- Demo seed for WL Sales
SET NAMES utf8mb4;
SET time_zone = '+03:00';

-- Admin: admin@white-list.space / ChangeMeAdmin2026! (сменить после деплоя)
INSERT INTO users (email, password_hash, name, telegram, role, is_banned, created_at) VALUES
('admin@white-list.space', '$2y$10$a.jYnWBz6h3UKaeK1XyDXerJaDNH4NtytpZm/R/rJIaQkq2RTToHW', 'Администратор', '', 'admin', 0, NOW());

INSERT INTO products (slug, title, vendor, location, subnet, cpu, ram_gb, disk_gb, nic, traffic, description, price_rent, price_forever, price_inst_2, price_inst_4, status, sort, created_at) VALUES
('selectel-spb-4-8', 'Selectel VPS 4/8 — СПб', 'Selectel', 'Санкт-Петербург', '95.163.x.0/24', '4 vCPU', 8, 80, '1 Gbit', 'безлимит', 'Публичный IPv4 из проверенной российской подсети. Чистая ОС, root-доступ. IP проверяется перед выдачей.', 7500.00, 32000.00, 16000.00, 28000.00, 'available', 10, NOW()),
('timeweb-msk-2-4', 'Timeweb VPS 2/4 — Москва', 'Timeweb', 'Москва', '92.53.x.0/24', '2 vCPU', 4, 40, '1 Gbit', 'безлимит', 'Компактный VPS с публичным IP. Подходит для тестов и лёгких сервисов.', 6200.00, 25000.00, 13000.00, 22000.00, 'available', 20, NOW()),
('mws-msk-4-8', 'MWS Cloud 4/8 — Москва', 'MWS', 'Москва', '178.248.x.0/24', '4 vCPU', 8, 100, '1 Gbit', 'безлимит', 'Сервер на инфраструктуре MWS. Публичный адрес, выдача доступов в кабинет после оплаты.', 8200.00, 38000.00, 17500.00, 32000.00, 'available', 30, NOW()),
('ruvds-msk-8-16', 'RuVDS Dedicated-like 8/16', 'RuVDS', 'Москва', '185.12.x.0/24', '8 vCPU', 16, 200, '1 Gbit', 'безлимит', 'Мощный лот под нагрузку. Аренда помесячно или выкуп навсегда.', 9800.00, 48000.00, 21000.00, 40000.00, 'available', 40, NOW()),
('yandex-msk-4-8', 'Yandex Cloud 4/8 — Москва', 'Yandex Cloud', 'Москва', '51.250.x.0/24', '4 vCPU', 8, 96, '1 Gbit', 'безлимит', 'Инстанс с публичным IP в облаке Yandex. Статус «под заказ» — сборка 1–3 дня.', 8900.00, 42000.00, 19000.00, 36000.00, 'preorder', 50, NOW()),
('vk-spb-2-4', 'VK Cloud 2/4 — СПб', 'VK Cloud', 'Санкт-Петербург', '87.250.x.0/24', '2 vCPU', 4, 50, '1 Gbit', 'безлимит', 'Небольшой лот VK Cloud с публичным адресом. Проверка доступности IP перед передачей.', 6900.00, 28000.00, 14500.00, 24500.00, 'available', 60, NOW());

INSERT INTO settings (k, v) VALUES
('site_telegram', 'https://t.me/wlsales_support'),
('site_email', 'support@white-list.space'),
('site_phone', ''),
('requisites', 'ООО «Пример»\nИНН 0000000000\nОГРН 0000000000000\nр/с 40702810000000000000'),
('sbp_phone', '+7 (900) 000-00-00'),
('sbp_comment', 'Оплата заказа WL Sales #{order_id}'),
('crypto_usdt_trc20', 'TXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'),
('offer_html', '<p>Настоящая оферта определяет условия продажи серверов с публичным IP. Актуальная редакция публикуется на сайте.</p>'),
('privacy_html', '<p>Мы обрабатываем email, Telegram и данные заказов для исполнения договора. Подробности — по запросу.</p>'),
('yookassa_enabled', '0');
