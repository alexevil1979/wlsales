-- WL Sales: линейка «Белый вход» (proxy)
-- MySQL 5.7+, utf8mb4, InnoDB. Безопасно поверх существующей БД.

SET NAMES utf8mb4;
SET time_zone = '+03:00';

-- Расширение orders под proxy
ALTER TABLE orders
  MODIFY product_id INT UNSIGNED NULL,
  MODIFY tariff ENUM('rent','inst2','inst4','forever','proxy') NOT NULL;

-- order_type / ref_id (идемпотентно через information_schema)
SET @c1 := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'order_type'
);
SET @s1 := IF(@c1 = 0,
  'ALTER TABLE orders ADD COLUMN order_type ENUM(''server'',''proxy'') NOT NULL DEFAULT ''server'' AFTER user_id',
  'SELECT 1');
PREPARE ps1 FROM @s1; EXECUTE ps1; DEALLOCATE PREPARE ps1;

SET @c2 := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'ref_id'
);
SET @s2 := IF(@c2 = 0,
  'ALTER TABLE orders ADD COLUMN ref_id INT UNSIGNED NULL AFTER product_id',
  'SELECT 1');
PREPARE ps2 FROM @s2; EXECUTE ps2; DEALLOCATE PREPARE ps2;

CREATE TABLE IF NOT EXISTS proxy_plans (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(80) NOT NULL,
  title VARCHAR(160) NOT NULL,
  domains_limit SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  price_month DECIMAL(12,2) NOT NULL DEFAULT 0,
  dedicated_ip TINYINT(1) NOT NULL DEFAULT 0,
  sort INT NOT NULL DEFAULT 100,
  active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_proxy_plans_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proxy_nodes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  server_id INT UNSIGNED NULL,
  hostname VARCHAR(190) NOT NULL DEFAULT '',
  public_ip VARCHAR(64) NOT NULL,
  location VARCHAR(80) NOT NULL DEFAULT '',
  vendor VARCHAR(80) NOT NULL DEFAULT '',
  domains_used INT UNSIGNED NOT NULL DEFAULT 0,
  domains_cap INT UNSIGNED NOT NULL DEFAULT 50,
  status ENUM('active','full','maintenance','dead') NOT NULL DEFAULT 'active',
  note_admin TEXT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_proxy_nodes_status (status),
  KEY idx_proxy_nodes_server (server_id),
  CONSTRAINT fk_proxy_nodes_server FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proxy_subscriptions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  node_id INT UNSIGNED NULL,
  status ENUM('new','awaiting_payment','active','suspended','cancelled') NOT NULL DEFAULT 'new',
  period_end DATE NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_proxy_subs_user (user_id),
  KEY idx_proxy_subs_status (status),
  CONSTRAINT fk_proxy_subs_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_proxy_subs_plan FOREIGN KEY (plan_id) REFERENCES proxy_plans(id),
  CONSTRAINT fk_proxy_subs_node FOREIGN KEY (node_id) REFERENCES proxy_nodes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proxy_domains (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  subscription_id INT UNSIGNED NOT NULL,
  domain VARCHAR(190) NOT NULL,
  origin_host VARCHAR(190) NOT NULL,
  origin_port SMALLINT UNSIGNED NOT NULL DEFAULT 443,
  origin_https TINYINT(1) NOT NULL DEFAULT 1,
  origin_sni VARCHAR(190) NULL,
  ssl_status ENUM('pending','issued','error') NOT NULL DEFAULT 'pending',
  status ENUM('pending_dns','active','error','disabled') NOT NULL DEFAULT 'pending_dns',
  last_check_at DATETIME NULL,
  last_check_ok TINYINT(1) NOT NULL DEFAULT 0,
  note_admin TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_proxy_domains_sub (subscription_id),
  KEY idx_proxy_domains_status (status),
  UNIQUE KEY uq_proxy_domains_domain (domain),
  CONSTRAINT fk_proxy_domains_sub FOREIGN KEY (subscription_id) REFERENCES proxy_subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO proxy_plans (slug, title, domains_limit, price_month, dedicated_ip, sort, active)
SELECT * FROM (
  SELECT 'proxy-1' AS slug, '1 домен / мес' AS title, 1 AS domains_limit, 2900.00 AS price_month, 0 AS dedicated_ip, 10 AS sort, 1 AS active
  UNION ALL SELECT 'proxy-3', 'Пак 3 домена / мес', 3, 6900.00, 0, 20, 1
  UNION ALL SELECT 'proxy-5', 'Пак 5 доменов / мес', 5, 9900.00, 0, 30, 1
  UNION ALL SELECT 'proxy-10', 'Пак 10 доменов / мес', 10, 15900.00, 0, 40, 1
  UNION ALL SELECT 'proxy-dedicated', 'Выделенный белый вход (отдельный IP)', 20, 24900.00, 1, 50, 1
) AS t
WHERE NOT EXISTS (SELECT 1 FROM proxy_plans LIMIT 1);
