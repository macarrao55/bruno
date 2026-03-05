PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  username TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL CHECK(role IN ('Admin','Caixa','Cozinha','Entregador')),
  active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  category_id INTEGER,
  price REAL NOT NULL,
  cost REAL NOT NULL DEFAULT 0,
  active INTEGER NOT NULL DEFAULT 1,
  prep_time INTEGER NOT NULL DEFAULT 0,
  image_url TEXT,
  FOREIGN KEY(category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS clients (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  phone TEXT,
  address TEXT,
  notes TEXT,
  credit_limit REAL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS riders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  phone TEXT,
  active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS neighborhoods (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT UNIQUE NOT NULL,
  delivery_fee REAL NOT NULL,
  avg_time INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  created_at TEXT NOT NULL,
  origin TEXT NOT NULL,
  order_type TEXT NOT NULL,
  client_id INTEGER,
  neighborhood_id INTEGER,
  rider_id INTEGER,
  status TEXT NOT NULL DEFAULT 'aberto',
  kitchen_status TEXT NOT NULL DEFAULT 'novo',
  delivery_status TEXT NOT NULL DEFAULT 'aguardando',
  payment_method TEXT NOT NULL,
  subtotal REAL NOT NULL,
  discount_type TEXT,
  discount_value REAL DEFAULT 0,
  service_fee REAL DEFAULT 0,
  delivery_fee REAL DEFAULT 0,
  total REAL NOT NULL,
  notes TEXT,
  cash_received REAL DEFAULT 0,
  change_value REAL DEFAULT 0,
  FOREIGN KEY(client_id) REFERENCES clients(id),
  FOREIGN KEY(neighborhood_id) REFERENCES neighborhoods(id),
  FOREIGN KEY(rider_id) REFERENCES riders(id)
);

CREATE TABLE IF NOT EXISTS order_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  product_id INTEGER,
  product_name TEXT NOT NULL,
  qty INTEGER NOT NULL,
  unit_price REAL NOT NULL,
  total_price REAL NOT NULL,
  notes TEXT,
  FOREIGN KEY(order_id) REFERENCES orders(id)
);

CREATE TABLE IF NOT EXISTS ingredients (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  unit TEXT NOT NULL,
  stock_current REAL NOT NULL DEFAULT 0,
  stock_min REAL NOT NULL DEFAULT 0,
  avg_cost REAL NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS product_recipes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  ingredient_id INTEGER NOT NULL,
  qty REAL NOT NULL,
  FOREIGN KEY(product_id) REFERENCES products(id),
  FOREIGN KEY(ingredient_id) REFERENCES ingredients(id)
);

CREATE TABLE IF NOT EXISTS stock_movements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ingredient_id INTEGER NOT NULL,
  movement_type TEXT NOT NULL,
  qty REAL NOT NULL,
  note TEXT,
  created_at TEXT NOT NULL,
  FOREIGN KEY(ingredient_id) REFERENCES ingredients(id)
);

CREATE TABLE IF NOT EXISTS cash_register (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  opened_at TEXT,
  closed_at TEXT,
  opening_amount REAL DEFAULT 0,
  closing_amount REAL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'aberto'
);

CREATE TABLE IF NOT EXISTS cash_movements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  movement_type TEXT NOT NULL,
  amount REAL NOT NULL,
  note TEXT,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS expenses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  description TEXT NOT NULL,
  amount REAL NOT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER,
  action TEXT NOT NULL,
  module TEXT NOT NULL,
  details TEXT,
  created_at TEXT NOT NULL,
  FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);
CREATE INDEX IF NOT EXISTS idx_orders_kitchen_status ON orders(kitchen_status);
CREATE INDEX IF NOT EXISTS idx_stock_current ON ingredients(stock_current);
