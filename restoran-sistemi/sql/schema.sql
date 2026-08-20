-- ============================================================
-- KAFE YÖNETİM SİSTEMİ - Veritabanı Şeması
-- MySQL 8+
-- ============================================================

CREATE DATABASE IF NOT EXISTS kafe_yonetim
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kafe_yonetim;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS cashier_closings;
DROP TABLE IF EXISTS cash_registers;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS stock_count_items;
DROP TABLE IF EXISTS stock_counts;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS recipe_items;
DROP TABLE IF EXISTS recipes;
DROP TABLE IF EXISTS stock_items;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS table_sessions;
DROP TABLE IF EXISTS cafe_tables;
DROP TABLE IF EXISTS product_prices;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- users
-- ------------------------------------------------------------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  username      VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','yonetici','garson','kasa','mutfak','depo') NOT NULL,
  discount_limit DECIMAL(5,2) NOT NULL DEFAULT 0,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- settings
-- ------------------------------------------------------------
CREATE TABLE settings (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(80) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- categories / products
-- ------------------------------------------------------------
CREATE TABLE categories (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(80) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  category_id    INT NULL,
  name           VARCHAR(120) NOT NULL,
  description    TEXT NULL,
  image_url      VARCHAR(255) NULL,
  sale_price     DECIMAL(10,2) NOT NULL DEFAULT 0,
  cost_price     DECIMAL(10,2) NOT NULL DEFAULT 0,
  vat_rate       DECIMAL(5,2) NOT NULL DEFAULT 10,
  track_stock    TINYINT(1) NOT NULL DEFAULT 0,
  min_stock_qty  DECIMAL(10,2) NOT NULL DEFAULT 0,
  kitchen_station ENUM('mutfak','bar','yok') NOT NULL DEFAULT 'mutfak',
  is_active      TINYINT(1) NOT NULL DEFAULT 1,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tarih bazlı fiyatlandırma için hazırlık
CREATE TABLE product_prices (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  price      DECIMAL(10,2) NOT NULL,
  valid_from DATETIME NOT NULL,
  valid_to   DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pp_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- masalar / session
-- ------------------------------------------------------------
CREATE TABLE cafe_tables (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(40) NOT NULL,
  section    VARCHAR(60) NOT NULL DEFAULT 'İç Alan',
  capacity   INT NOT NULL DEFAULT 4,
  status     ENUM('bos','dolu','rezerve','hesap_bekliyor','kapali') NOT NULL DEFAULT 'bos',
  sort_order INT NOT NULL DEFAULT 0,
  qr_token   VARCHAR(64) NOT NULL UNIQUE,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE table_sessions (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  table_id       INT NOT NULL,
  opened_by      INT NULL,
  customer_count INT NOT NULL DEFAULT 1,
  status         ENUM('open','paid','cancelled','merged') NOT NULL DEFAULT 'open',
  discount_type  ENUM('none','percentage','fixed') NOT NULL DEFAULT 'none',
  discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
  merged_into_id INT NULL,
  opened_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at      DATETIME NULL,
  notes          TEXT NULL,
  CONSTRAINT fk_ts_table FOREIGN KEY (table_id) REFERENCES cafe_tables(id),
  CONSTRAINT fk_ts_user FOREIGN KEY (opened_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_ts_table_status ON table_sessions(table_id, status);

-- ------------------------------------------------------------
-- siparişler
-- ------------------------------------------------------------
CREATE TABLE orders (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  waiter_id  INT NULL,
  status     ENUM('draft','sent','closed','cancelled') NOT NULL DEFAULT 'draft',
  notes      TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_session FOREIGN KEY (session_id) REFERENCES table_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_orders_waiter FOREIGN KEY (waiter_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  order_id         INT NOT NULL,
  product_id       INT NOT NULL,
  qty              DECIMAL(10,2) NOT NULL DEFAULT 1,
  unit_price       DECIMAL(10,2) NOT NULL,
  discount_amount  DECIMAL(10,2) NOT NULL DEFAULT 0,
  is_complimentary TINYINT(1) NOT NULL DEFAULT 0,
  complimentary_reason VARCHAR(255) NULL,
  note             VARCHAR(255) NULL,
  status           ENUM('draft','sent','preparing','ready','served','cancelled') NOT NULL DEFAULT 'draft',
  stock_applied    TINYINT(1) NOT NULL DEFAULT 0,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_oi_status ON order_items(status);

-- ------------------------------------------------------------
-- ödemeler
-- ------------------------------------------------------------
CREATE TABLE payments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  session_id  INT NOT NULL,
  amount      DECIMAL(10,2) NOT NULL,
  method      ENUM('nakit','kredi_karti','yemek_karti','havale','diger') NOT NULL,
  received_by INT NULL,
  paid_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pay_session FOREIGN KEY (session_id) REFERENCES table_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_pay_user FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- stok / reçete
-- ------------------------------------------------------------
CREATE TABLE stock_items (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  unit        VARCHAR(20) NOT NULL DEFAULT 'adet',
  current_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
  min_qty     DECIMAL(12,3) NOT NULL DEFAULT 0,
  cost_price  DECIMAL(10,4) NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_movements (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  stock_item_id  INT NOT NULL,
  type           ENUM('purchase','sale','manual_in','manual_out','waste','count_adjustment') NOT NULL,
  quantity       DECIMAL(12,3) NOT NULL,
  unit_cost      DECIMAL(10,4) NULL,
  reference_type VARCHAR(40) NULL,
  reference_id   INT NULL,
  reason         VARCHAR(255) NULL,
  created_by     INT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sm_item FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_sm_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE recipes (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  name       VARCHAR(120) NOT NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rec_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE recipe_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  recipe_id     INT NOT NULL,
  stock_item_id INT NOT NULL,
  quantity      DECIMAL(12,3) NOT NULL,
  unit          VARCHAR(20) NOT NULL,
  CONSTRAINT fk_ri_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
  CONSTRAINT fk_ri_item FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_counts (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  created_by INT NULL,
  note       VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sc_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_count_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  count_id      INT NOT NULL,
  stock_item_id INT NOT NULL,
  system_qty    DECIMAL(12,3) NOT NULL,
  counted_qty   DECIMAL(12,3) NOT NULL,
  diff_qty      DECIMAL(12,3) NOT NULL,
  reason        ENUM('fire','kayip','sayim_hatasi','diger') NOT NULL DEFAULT 'diger',
  CONSTRAINT fk_sci_count FOREIGN KEY (count_id) REFERENCES stock_counts(id) ON DELETE CASCADE,
  CONSTRAINT fk_sci_item FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- tedarikçi / satın alma
-- ------------------------------------------------------------
CREATE TABLE suppliers (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(150) NOT NULL,
  phone      VARCHAR(40) NULL,
  email      VARCHAR(120) NULL,
  address    TEXT NULL,
  tax_office VARCHAR(120) NULL,
  tax_number VARCHAR(40) NULL,
  note       TEXT NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchases (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  supplier_id    INT NULL,
  invoice_no     VARCHAR(60) NULL,
  purchase_date  DATE NOT NULL,
  total_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,
  status         ENUM('draft','approved','cancelled') NOT NULL DEFAULT 'draft',
  created_by     INT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pur_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  CONSTRAINT fk_pur_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id   INT NOT NULL,
  stock_item_id INT NOT NULL,
  quantity      DECIMAL(12,3) NOT NULL,
  unit_price    DECIMAL(10,4) NOT NULL,
  line_total    DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_pi_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
  CONSTRAINT fk_pi_item FOREIGN KEY (stock_item_id) REFERENCES stock_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- kasa
-- ------------------------------------------------------------
CREATE TABLE cash_registers (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  opened_by     INT NULL,
  opening_cash  DECIMAL(12,2) NOT NULL DEFAULT 0,
  opened_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at     DATETIME NULL,
  status        ENUM('open','closed') NOT NULL DEFAULT 'open',
  CONSTRAINT fk_cr_user FOREIGN KEY (opened_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cashier_closings (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  register_id    INT NOT NULL,
  closed_by      INT NULL,
  opening_cash   DECIMAL(12,2) NOT NULL DEFAULT 0,
  cash_sales     DECIMAL(12,2) NOT NULL DEFAULT 0,
  card_sales     DECIMAL(12,2) NOT NULL DEFAULT 0,
  other_sales    DECIMAL(12,2) NOT NULL DEFAULT 0,
  expected_cash  DECIMAL(12,2) NOT NULL DEFAULT 0,
  counted_cash   DECIMAL(12,2) NOT NULL DEFAULT 0,
  difference     DECIMAL(12,2) NOT NULL DEFAULT 0,
  note           TEXT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cc_reg FOREIGN KEY (register_id) REFERENCES cash_registers(id) ON DELETE CASCADE,
  CONSTRAINT fk_cc_user FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- audit log
-- ------------------------------------------------------------
CREATE TABLE audit_logs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NULL,
  action      VARCHAR(80) NOT NULL,
  entity_type VARCHAR(60) NULL,
  entity_id   INT NULL,
  old_data    JSON NULL,
  new_data    JSON NULL,
  ip          VARCHAR(45) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_al_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_al_created ON audit_logs(created_at);
