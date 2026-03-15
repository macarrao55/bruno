CREATE DATABASE IF NOT EXISTS mega_lanches_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mega_lanches_erp;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  slug VARCHAR(50) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_role_permission (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id),
  FOREIGN KEY (permission_id) REFERENCES permissions(id)
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE user_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  permission_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user_permission (user_id, permission_id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (permission_id) REFERENCES permissions(id)
);

CREATE TABLE system_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
);

CREATE TABLE employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  name VARCHAR(120) NOT NULL,
  position VARCHAR(80) NOT NULL,
  phone VARCHAR(20),
  address VARCHAR(255),
  admission_date DATE,
  salary DECIMAL(10,2) DEFAULT 0,
  commission_percent DECIMAL(5,2) DEFAULT 0,
  status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone_main VARCHAR(20) NOT NULL,
  phone_secondary VARCHAR(20),
  birth_date DATE,
  notes TEXT,
  register_date DATE DEFAULT (CURRENT_DATE),
  last_purchase_at DATETIME NULL,
  total_spent DECIMAL(12,2) NOT NULL DEFAULT 0,
  orders_count INT NOT NULL DEFAULT 0,
  status ENUM('ativo','inativo') DEFAULT 'ativo',
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  INDEX idx_customers_phone (phone_main),
  INDEX idx_customers_name (name)
);

CREATE TABLE customer_addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  street VARCHAR(150),
  number VARCHAR(30),
  complement VARCHAR(120),
  neighborhood VARCHAR(80),
  city VARCHAR(80),
  reference_point VARCHAR(150),
  is_default TINYINT(1) DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE product_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  parent_id INT NULL,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (parent_id) REFERENCES product_categories(id)
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  sale_price DECIMAL(10,2) NOT NULL,
  cost_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  margin_percent DECIMAL(6,2) DEFAULT 0,
  photo VARCHAR(255),
  promo_price DECIMAL(10,2) NULL,
  promo_weekday TINYINT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  stock_control TINYINT(1) NOT NULL DEFAULT 0,
  allow_addons TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (category_id) REFERENCES product_categories(id),
  INDEX idx_products_name (name)
);

CREATE TABLE addon_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  min_select INT DEFAULT 0,
  max_select INT DEFAULT 1,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
);

CREATE TABLE product_addons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  name VARCHAR(80) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (group_id) REFERENCES addon_groups(id)
);

CREATE TABLE product_addon_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  addon_group_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_product_group (product_id, addon_group_id),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (addon_group_id) REFERENCES addon_groups(id)
);

CREATE TABLE stock_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(80),
  unit VARCHAR(20) NOT NULL,
  current_stock DECIMAL(10,3) NOT NULL DEFAULT 0,
  min_stock DECIMAL(10,3) NOT NULL DEFAULT 0,
  average_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('ativo','inativo') DEFAULT 'ativo',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL
);

CREATE TABLE stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stock_item_id INT NOT NULL,
  movement_type ENUM('entrada','saida','ajuste') NOT NULL,
  quantity DECIMAL(10,3) NOT NULL,
  unit_cost DECIMAL(10,2) DEFAULT 0,
  notes TEXT,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (stock_item_id) REFERENCES stock_items(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE product_recipes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  stock_item_id INT NOT NULL,
  quantity_used DECIMAL(10,3) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_recipe (product_id, stock_item_id),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (stock_item_id) REFERENCES stock_items(id)
);

CREATE TABLE delivery_zones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  neighborhood VARCHAR(80) NOT NULL,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  estimated_minutes INT DEFAULT 40,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  discount_type ENUM('percentual','valor_fixo') NOT NULL,
  discount_value DECIMAL(10,2) NOT NULL,
  min_order_value DECIMAL(10,2) DEFAULT 0,
  starts_at DATETIME,
  ends_at DATETIME,
  usage_limit INT DEFAULT 0,
  used_count INT DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NULL,
  delivery_zone_id INT NULL,
  coupon_id INT NULL,
  order_type ENUM('balcao','delivery','retirada') NOT NULL,
  status ENUM('novo','em preparo','pronto','saiu para entrega','entregue','cancelado') NOT NULL DEFAULT 'novo',
  payment_method VARCHAR(40) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  change_for DECIMAL(10,2) NULL,
  notes TEXT,
  cancel_reason VARCHAR(255),
  created_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (delivery_zone_id) REFERENCES delivery_zones(id),
  FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX idx_orders_status (status),
  INDEX idx_orders_created_at (created_at)
);

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NULL,
  product_name VARCHAR(120) NOT NULL,
  quantity DECIMAL(10,3) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE order_item_addons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_item_id INT NOT NULL,
  addon_name VARCHAR(80) NOT NULL,
  addon_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  quantity INT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_item_id) REFERENCES order_items(id)
);

CREATE TABLE cash_registers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  operator_id INT NOT NULL,
  opened_at DATETIME NOT NULL,
  opening_amount DECIMAL(10,2) NOT NULL,
  opening_notes VARCHAR(255),
  closed_at DATETIME NULL,
  closing_amount DECIMAL(10,2) NULL,
  expected_amount DECIMAL(10,2) NULL,
  difference_amount DECIMAL(10,2) NULL,
  status ENUM('aberto','fechado') NOT NULL DEFAULT 'aberto',
  closing_notes VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (operator_id) REFERENCES users(id)
);

CREATE TABLE cash_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cash_register_id INT NULL,
  type ENUM('entrada','saida','sangria','reforco','venda') NOT NULL,
  description VARCHAR(200) NOT NULL,
  payment_method VARCHAR(40) NOT NULL DEFAULT 'dinheiro',
  amount DECIMAL(10,2) NOT NULL,
  order_id INT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cash_register_id) REFERENCES cash_registers(id),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE financial_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entry_type ENUM('receita','despesa','transferencia') NOT NULL,
  source VARCHAR(40),
  source_id INT NULL,
  description VARCHAR(200) NOT NULL,
  category VARCHAR(80),
  subcategory VARCHAR(80),
  origin VARCHAR(80),
  payment_method VARCHAR(40),
  amount DECIMAL(10,2) NOT NULL,
  cost_center VARCHAR(80),
  notes TEXT,
  status ENUM('previsto','realizado','cancelado') NOT NULL DEFAULT 'realizado',
  entry_date DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_financial_date (entry_date)
);

CREATE TABLE accounts_receivable (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  order_id INT NULL,
  description VARCHAR(200) NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  due_date DATE,
  status ENUM('pendente','parcial','pago','atrasado') DEFAULT 'pendente',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE loyalty_points (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  points_balance INT NOT NULL DEFAULT 0,
  points_earned INT NOT NULL DEFAULT 0,
  points_used INT NOT NULL DEFAULT 0,
  cashback_balance DECIMAL(10,2) NOT NULL DEFAULT 0,
  updated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_loyalty_customer (customer_id),
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE loyalty_rewards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  points_required INT NOT NULL,
  reward_type ENUM('produto','desconto','cashback') NOT NULL,
  reward_value DECIMAL(10,2) NOT NULL DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE loyalty_redemptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  reward_id INT NOT NULL,
  points_used INT NOT NULL,
  order_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (reward_id) REFERENCES loyalty_rewards(id),
  FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE marketing_campaigns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  campaign_type VARCHAR(50) NOT NULL,
  message_template TEXT,
  status ENUM('rascunho','ativa','finalizada','cancelada') DEFAULT 'rascunho',
  start_date DATE,
  end_date DATE,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE marketing_campaign_customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT NOT NULL,
  customer_id INT NOT NULL,
  sent_at DATETIME NULL,
  delivery_status VARCHAR(40),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_campaign_customer (campaign_id, customer_id),
  FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id),
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(120) NOT NULL,
  entity VARCHAR(80) NULL,
  entity_id INT NULL,
  old_data JSON NULL,
  new_data JSON NULL,
  ip_address VARCHAR(45),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created (created_at),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
