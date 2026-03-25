CREATE DATABASE IF NOT EXISTS mega_lanches_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mega_lanches_erp;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE system_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  neighborhood VARCHAR(80),
  address VARCHAR(180),
  birth_date DATE NULL,
  notes TEXT NULL,
  total_spent DECIMAL(12,2) NOT NULL DEFAULT 0,
  orders_count INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_customer_phone (phone),
  INDEX idx_customer_name (name)
);

CREATE TABLE customer_addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  address VARCHAR(180) NOT NULL,
  neighborhood VARCHAR(80) NOT NULL,
  complement VARCHAR(120) NULL,
  reference_point VARCHAR(150) NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  controls_stock TINYINT(1) NOT NULL DEFAULT 0,
  allows_addons TINYINT(1) NOT NULL DEFAULT 1,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE addon_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE addons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  addon_group_id INT NOT NULL,
  name VARCHAR(80) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (addon_group_id) REFERENCES addon_groups(id)
);

CREATE TABLE product_addons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  addon_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_product_addon(product_id, addon_id),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (addon_id) REFERENCES addons(id)
);

CREATE TABLE stock_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  unit VARCHAR(20) NOT NULL,
  current_stock DECIMAL(10,3) NOT NULL DEFAULT 0,
  min_stock DECIMAL(10,3) NOT NULL DEFAULT 0,
  average_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
);

CREATE TABLE stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stock_item_id INT NOT NULL,
  movement_type ENUM('entrada','saida','ajuste') NOT NULL,
  quantity DECIMAL(10,3) NOT NULL,
  unit_cost DECIMAL(10,2) DEFAULT 0,
  notes VARCHAR(255) NULL,
  reference_type VARCHAR(40) NULL,
  reference_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (stock_item_id) REFERENCES stock_items(id)
);

CREATE TABLE product_recipes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  stock_item_id INT NOT NULL,
  quantity_used DECIMAL(10,3) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_recipe(product_id, stock_item_id),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (stock_item_id) REFERENCES stock_items(id)
);

CREATE TABLE cash_registers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  opened_at DATETIME NOT NULL,
  opening_amount DECIMAL(10,2) NOT NULL,
  closed_at DATETIME NULL,
  closing_amount DECIMAL(10,2) NULL,
  expected_amount DECIMAL(10,2) NULL,
  difference_amount DECIMAL(10,2) NULL,
  status ENUM('aberto','fechado') NOT NULL DEFAULT 'aberto',
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE cash_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cash_register_id INT NOT NULL,
  type ENUM('entrada','saida','venda','sangria','reforco') NOT NULL,
  payment_method VARCHAR(30) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(180) NOT NULL,
  order_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (cash_register_id) REFERENCES cash_registers(id)
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NULL,
  user_id INT NOT NULL,
  order_type ENUM('balcao','delivery','retirada') NOT NULL,
  status ENUM('novo','em preparo','pronto','entregue','cancelado') NOT NULL DEFAULT 'novo',
  payment_method VARCHAR(30) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL,
  change_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(120) NOT NULL,
  quantity DECIMAL(10,3) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  unit_cost DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE order_item_addons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_item_id INT NOT NULL,
  addon_id INT NOT NULL,
  addon_name VARCHAR(80) NOT NULL,
  addon_price DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (order_item_id) REFERENCES order_items(id),
  FOREIGN KEY (addon_id) REFERENCES addons(id)
);

CREATE TABLE financial_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('entrada','saida') NOT NULL,
  source VARCHAR(40) NOT NULL,
  source_id INT NULL,
  description VARCHAR(180) NOT NULL,
  category VARCHAR(80) NOT NULL,
  payment_method VARCHAR(30) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  entry_date DATE NOT NULL,
  status ENUM('realizado','cancelado') NOT NULL DEFAULT 'realizado',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
);

CREATE TABLE accounts_receivable (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  order_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  due_date DATE NOT NULL,
  status ENUM('pendente','parcial','pago') NOT NULL DEFAULT 'pendente',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE loyalty_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  order_id INT NULL,
  points INT NOT NULL,
  type ENUM('credito','debito') NOT NULL,
  description VARCHAR(180) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  entity VARCHAR(80) NOT NULL,
  entity_id INT NULL,
  details TEXT NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
