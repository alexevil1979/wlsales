-- WL Sales schema (MySQL 5.7+, utf8mb4, InnoDB)
SET NAMES utf8mb4;
SET time_zone = '+03:00';

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT '',
  telegram VARCHAR(120) NOT NULL DEFAULT '',
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  is_banned TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(160) NOT NULL,
  title VARCHAR(200) NOT NULL,
  vendor VARCHAR(80) NOT NULL,
  location VARCHAR(80) NOT NULL,
  subnet VARCHAR(64) NOT NULL DEFAULT '',
  cpu VARCHAR(80) NOT NULL DEFAULT '',
  ram_gb SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  disk_gb INT UNSIGNED NOT NULL DEFAULT 0,
  nic VARCHAR(80) NOT NULL DEFAULT '',
  traffic VARCHAR(80) NOT NULL DEFAULT '',
  description TEXT NULL,
  price_rent DECIMAL(12,2) NOT NULL DEFAULT 0,
  price_forever DECIMAL(12,2) NOT NULL DEFAULT 0,
  price_inst_2 DECIMAL(12,2) NOT NULL DEFAULT 0,
  price_inst_4 DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('available','reserved','sold','hidden','preorder') NOT NULL DEFAULT 'available',
  sort INT NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_slug (slug),
  KEY idx_products_status_sort (status, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NULL,
  hostname VARCHAR(190) NOT NULL DEFAULT '',
  ip VARCHAR(64) NOT NULL DEFAULT '',
  panel_url VARCHAR(255) NOT NULL DEFAULT '',
  login_hint VARCHAR(120) NOT NULL DEFAULT '',
  secret_enc TEXT NULL,
  note_admin TEXT NULL,
  assigned_user_id INT UNSIGNED NULL,
  assigned_order_id INT UNSIGNED NULL,
  status ENUM('free','reserved','assigned','offline') NOT NULL DEFAULT 'free',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_servers_product (product_id),
  KEY idx_servers_user (assigned_user_id),
  CONSTRAINT fk_servers_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  CONSTRAINT fk_servers_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  tariff ENUM('rent','inst2','inst4','forever') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'RUB',
  status ENUM('new','awaiting_payment','paid','delivered','cancelled','refund','pending_stock') NOT NULL DEFAULT 'new',
  payment_provider VARCHAR(40) NOT NULL DEFAULT '',
  admin_comment TEXT NULL,
  paid_at DATETIME NULL,
  delivered_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_orders_user (user_id),
  KEY idx_orders_status (status),
  KEY idx_orders_product (product_id),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_orders_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  provider VARCHAR(40) NOT NULL,
  external_id VARCHAR(190) NOT NULL DEFAULT '',
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('pending','waiting_confirm','succeeded','cancelled','failed') NOT NULL DEFAULT 'pending',
  raw_json MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_payments_order (order_id),
  KEY idx_payments_external (provider, external_id),
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  subject VARCHAR(200) NOT NULL,
  status ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_tickets_user (user_id),
  CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_tickets_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ticket_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_tm_ticket (ticket_id),
  CONSTRAINT fk_tm_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  CONSTRAINT fk_tm_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(80) NOT NULL,
  v MEDIUMTEXT NULL,
  PRIMARY KEY (k)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  action VARCHAR(120) NOT NULL,
  ip VARCHAR(64) NOT NULL DEFAULT '',
  meta TEXT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_admin_log_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip VARCHAR(64) NOT NULL,
  email VARCHAR(190) NOT NULL DEFAULT '',
  attempted_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_la_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK servers.assigned_order_id после orders
ALTER TABLE servers
  ADD CONSTRAINT fk_servers_order FOREIGN KEY (assigned_order_id) REFERENCES orders(id) ON DELETE SET NULL;
