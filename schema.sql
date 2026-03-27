CREATE TABLE bank_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    initial_balance REAL NOT NULL DEFAULT 0,
    current_balance REAL NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    movement_type TEXT NOT NULL CHECK (movement_type IN ('entrada', 'saida')),
    amount REAL NOT NULL,
    category TEXT NOT NULL,
    subcategory TEXT,
    origin_account TEXT,
    destination_account TEXT,
    description TEXT,
    occurred_on TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE accounts_payable (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    company TEXT,
    supplier_id INTEGER,
    supplier TEXT NOT NULL,
    payable_type TEXT,
    boleto_number TEXT,
    due_date TEXT NOT NULL,
    amount REAL NOT NULL,
    installment TEXT,
    status TEXT NOT NULL CHECK (status IN ('aberto', 'pago', 'atrasado')),
    reminder_date TEXT,
    paid_on TEXT,
    payment_method TEXT,
    bank_account_id INTEGER,
    discount REAL NOT NULL DEFAULT 0,
    addition REAL NOT NULL DEFAULT 0,
    late_interest REAL NOT NULL DEFAULT 0,
    paid_amount REAL,
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE accounts_receivable (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer TEXT NOT NULL,
    due_date TEXT NOT NULL,
    amount REAL NOT NULL,
    amount_received REAL NOT NULL DEFAULT 0,
    installment TEXT,
    is_credit_sale INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL CHECK (status IN ('aberto', 'recebido', 'atrasado', 'parcial')),
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE card_receivables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    machine TEXT NOT NULL,
    brand TEXT NOT NULL,
    card_type TEXT NOT NULL CHECK (card_type IN ('debito', 'credito_avista', 'credito_parcelado')),
    fee_percent REAL NOT NULL,
    gross_value REAL NOT NULL,
    net_value REAL NOT NULL,
    sale_date TEXT NOT NULL,
    expected_release_date TEXT NOT NULL,
    received INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE checks_control (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    check_type TEXT NOT NULL CHECK (check_type IN ('avista', 'parcelado')),
    customer TEXT NOT NULL,
    bank TEXT NOT NULL,
    check_number TEXT NOT NULL,
    due_date TEXT NOT NULL,
    amount REAL NOT NULL,
    cleared INTEGER NOT NULL DEFAULT 0,
    compensated INTEGER NOT NULL DEFAULT 0,
    returned INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bank_reconciliation (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bank_account_id INTEGER NOT NULL,
    movement_date TEXT NOT NULL,
    description TEXT,
    system_amount REAL NOT NULL,
    bank_amount REAL NOT NULL,
    reconciled INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
);

CREATE TABLE cash_closing (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    opening_date TEXT NOT NULL,
    opening_amount REAL NOT NULL,
    total_entries REAL NOT NULL,
    total_exits REAL NOT NULL,
    counted_amount REAL NOT NULL,
    cash_difference REAL NOT NULL,
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cashflow_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    parent_id INTEGER,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES cashflow_categories(id)
);

CREATE TABLE payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    cnpj TEXT,
    email TEXT,
    contact_number TEXT,
    salesperson TEXT,
    cep TEXT,
    state TEXT,
    city TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE payable_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE card_machines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE card_brands (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE card_payment_configs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    fee_percent REAL NOT NULL DEFAULT 0,
    release_days INTEGER NOT NULL DEFAULT 30,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE card_rate_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    machine_id INTEGER NOT NULL,
    brand_id INTEGER NOT NULL,
    payment_config_id INTEGER NOT NULL,
    fee_percent REAL NOT NULL,
    release_days INTEGER NOT NULL DEFAULT 30,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES card_machines(id),
    FOREIGN KEY (brand_id) REFERENCES card_brands(id),
    FOREIGN KEY (payment_config_id) REFERENCES card_payment_configs(id)
);

INSERT INTO bank_accounts (name, initial_balance, current_balance) VALUES
('Banco Principal', 10000, 10000),
('Banco Reserva', 2500, 2500);

INSERT INTO cashflow_categories (name, parent_id) VALUES
('Receitas', NULL),
('Custos', NULL),
('Despesas Fixas', NULL),
('Despesas Variáveis', NULL);

INSERT INTO cashflow_categories (name, parent_id)
SELECT 'Vendas', id FROM cashflow_categories WHERE name='Receitas';
INSERT INTO cashflow_categories (name, parent_id)
SELECT 'Serviços', id FROM cashflow_categories WHERE name='Receitas';
INSERT INTO cashflow_categories (name, parent_id)
SELECT 'Fornecedores', id FROM cashflow_categories WHERE name='Custos';
INSERT INTO cashflow_categories (name, parent_id)
SELECT 'Aluguel', id FROM cashflow_categories WHERE name='Despesas Fixas';

INSERT INTO payment_methods (name) VALUES
('Pix'),
('Boleto'),
('Transferência'),
('Cartão de Crédito'),
('Dinheiro');

INSERT INTO payable_types (name) VALUES
('Boleto'),
('DDA'),
('Cheque'),
('Cartão de Credito'),
('Notinha ( fiado)');

INSERT INTO card_machines (name) VALUES
('Stone'),
('Cielo'),
('PagSeguro');

INSERT INTO card_brands (name) VALUES
('Visa'),
('Mastercard'),
('Elo');

INSERT INTO card_payment_configs (name, fee_percent, release_days) VALUES
('debito', 1.99, 1),
('credito_avista', 3.49, 30),
('credito_parcelado', 4.99, 30);

INSERT INTO card_rate_rules (machine_id, brand_id, payment_config_id, fee_percent, release_days)
SELECT m.id, b.id, p.id, 0.99, 1
FROM card_machines m, card_brands b, card_payment_configs p
WHERE m.name='Cielo' AND b.name='Elo' AND p.name='debito';

INSERT INTO card_rate_rules (machine_id, brand_id, payment_config_id, fee_percent, release_days)
SELECT m.id, b.id, p.id, 1.33, 1
FROM card_machines m, card_brands b, card_payment_configs p
WHERE m.name='PagSeguro' AND b.name='Elo' AND p.name='debito';
