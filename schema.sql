CREATE TABLE bank_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
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
    supplier TEXT NOT NULL,
    due_date TEXT NOT NULL,
    amount REAL NOT NULL,
    installment TEXT,
    status TEXT NOT NULL CHECK (status IN ('aberto', 'pago', 'atrasado')),
    reminder_date TEXT,
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
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

INSERT INTO bank_accounts (name, current_balance) VALUES
('Banco Principal', 10000),
('Banco Reserva', 2500);
