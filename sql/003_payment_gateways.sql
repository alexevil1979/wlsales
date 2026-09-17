-- Платёжные шлюзы как в fullvpnservice (payment_gateways)
CREATE TABLE IF NOT EXISTS payment_gateways (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  test_mode TINYINT(1) NOT NULL DEFAULT 1,
  min_amount_rub DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  config MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_gateways_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
