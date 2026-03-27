<?php

declare(strict_types=1);

const DB_PATH = __DIR__ . '/data/finance.db';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0777, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    initializeDatabase($pdo);

    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec('PRAGMA foreign_keys = ON;');

    $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='transactions'")->fetch();
    if (!$exists) {
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Não foi possível carregar schema.sql');
        }

        $pdo->exec($schema);
    }

    runMigrations($pdo);
}

function runMigrations(PDO $pdo): void
{
    $columns = $pdo->query("PRAGMA table_info(bank_accounts)")->fetchAll();
    $hasInitialBalance = false;

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'initial_balance') {
            $hasInitialBalance = true;
            break;
        }
    }

    if (!$hasInitialBalance) {
        $pdo->exec('ALTER TABLE bank_accounts ADD COLUMN initial_balance REAL NOT NULL DEFAULT 0');
        $pdo->exec('UPDATE bank_accounts SET initial_balance = current_balance WHERE initial_balance = 0');
    }

    $payableColumns = $pdo->query("PRAGMA table_info(accounts_payable)")->fetchAll();
    $payableColumnNames = array_map(static fn(array $column): string => (string) ($column['name'] ?? ''), $payableColumns);
    $addPayableColumn = static function (PDO $conn, string $name, string $type) use ($payableColumnNames): void {
        if (!in_array($name, $payableColumnNames, true)) {
            $conn->exec("ALTER TABLE accounts_payable ADD COLUMN $name $type");
        }
    };
    $addPayableColumn($pdo, 'company', 'TEXT');
    $addPayableColumn($pdo, 'company_id', 'INTEGER');
    $addPayableColumn($pdo, 'supplier_id', 'INTEGER');
    $addPayableColumn($pdo, 'payable_type', 'TEXT');
    $addPayableColumn($pdo, 'paid_on', 'TEXT');
    $addPayableColumn($pdo, 'payment_method', 'TEXT');
    $addPayableColumn($pdo, 'bank_account_id', 'INTEGER');
    $addPayableColumn($pdo, 'boleto_number', 'TEXT');
    $addPayableColumn($pdo, 'discount', 'REAL NOT NULL DEFAULT 0');
    $addPayableColumn($pdo, 'addition', 'REAL NOT NULL DEFAULT 0');
    $addPayableColumn($pdo, 'late_interest', 'REAL NOT NULL DEFAULT 0');
    $addPayableColumn($pdo, 'paid_amount', 'REAL');

    $cardColumns = $pdo->query("PRAGMA table_info(card_receivables)")->fetchAll();
    $cardColumnNames = array_map(static fn(array $column): string => (string) ($column['name'] ?? ''), $cardColumns);
    $addCardColumn = static function (PDO $conn, string $name, string $type) use ($cardColumnNames): void {
        if (!in_array($name, $cardColumnNames, true)) {
            $conn->exec("ALTER TABLE card_receivables ADD COLUMN $name $type");
        }
    };
    $addCardColumn($pdo, 'anticipation_discount', 'REAL NOT NULL DEFAULT 0');
    $addCardColumn($pdo, 'canceled', 'INTEGER NOT NULL DEFAULT 0');
    $addCardColumn($pdo, 'sale_location', 'TEXT');

    $categoryTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='cashflow_categories'")->fetch();
    if (!$categoryTable) {
        $pdo->exec('CREATE TABLE cashflow_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            parent_id INTEGER,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES cashflow_categories(id)
        )');
    }

    $totalCategories = (int) ($pdo->query('SELECT COUNT(*) FROM cashflow_categories')->fetchColumn() ?: 0);
    if ($totalCategories === 0) {
        $pdo->exec("INSERT INTO cashflow_categories (name, parent_id) VALUES
            ('Receitas', NULL), ('Custos', NULL), ('Despesas Fixas', NULL), ('Despesas Variáveis', NULL)");
        $pdo->exec("INSERT INTO cashflow_categories (name, parent_id)
            SELECT 'Vendas', id FROM cashflow_categories WHERE name='Receitas'");
        $pdo->exec("INSERT INTO cashflow_categories (name, parent_id)
            SELECT 'Serviços', id FROM cashflow_categories WHERE name='Receitas'");
        $pdo->exec("INSERT INTO cashflow_categories (name, parent_id)
            SELECT 'Fornecedores', id FROM cashflow_categories WHERE name='Custos'");
        $pdo->exec("INSERT INTO cashflow_categories (name, parent_id)
            SELECT 'Aluguel', id FROM cashflow_categories WHERE name='Despesas Fixas'");
    }

    $paymentMethodTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='payment_methods'")->fetch();
    if (!$paymentMethodTable) {
        $pdo->exec('CREATE TABLE payment_methods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $supplierTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='suppliers'")->fetch();
    if (!$supplierTable) {
        $pdo->exec('CREATE TABLE suppliers (
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
        )');
    }

    $paymentMethodsCount = (int) ($pdo->query('SELECT COUNT(*) FROM payment_methods')->fetchColumn() ?: 0);
    if ($paymentMethodsCount === 0) {
        $pdo->exec("INSERT INTO payment_methods (name) VALUES
            ('Pix'), ('Boleto'), ('Transferência'), ('Cartão de Crédito'), ('Dinheiro')");
    }

    $companyTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='companies'")->fetch();
    if (!$companyTable) {
        $pdo->exec('CREATE TABLE companies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $payableTypeTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='payable_types'")->fetch();
    if (!$payableTypeTable) {
        $pdo->exec('CREATE TABLE payable_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $payableTypesCount = (int) ($pdo->query('SELECT COUNT(*) FROM payable_types')->fetchColumn() ?: 0);
    if ($payableTypesCount === 0) {
        $pdo->exec("INSERT INTO payable_types (name) VALUES
            ('Boleto'), ('DDA'), ('Cheque'), ('Cartão de Credito'), ('Notinha ( fiado)')");
    }

    $cardMachineTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='card_machines'")->fetch();
    if (!$cardMachineTable) {
        $pdo->exec('CREATE TABLE card_machines (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $cardBrandTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='card_brands'")->fetch();
    if (!$cardBrandTable) {
        $pdo->exec('CREATE TABLE card_brands (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $cardPaymentConfigTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='card_payment_configs'")->fetch();
    if (!$cardPaymentConfigTable) {
        $pdo->exec('CREATE TABLE card_payment_configs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            fee_percent REAL NOT NULL DEFAULT 0,
            release_days INTEGER NOT NULL DEFAULT 30,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $cardMachineCount = (int) ($pdo->query('SELECT COUNT(*) FROM card_machines')->fetchColumn() ?: 0);
    if ($cardMachineCount === 0) {
        $pdo->exec("INSERT INTO card_machines (name) VALUES ('Stone'), ('Cielo'), ('PagSeguro')");
    }

    $cardBrandCount = (int) ($pdo->query('SELECT COUNT(*) FROM card_brands')->fetchColumn() ?: 0);
    if ($cardBrandCount === 0) {
        $pdo->exec("INSERT INTO card_brands (name) VALUES ('Visa'), ('Mastercard'), ('Elo')");
    }

    $cardPaymentConfigCount = (int) ($pdo->query('SELECT COUNT(*) FROM card_payment_configs')->fetchColumn() ?: 0);
    if ($cardPaymentConfigCount === 0) {
        $pdo->exec("INSERT INTO card_payment_configs (name, fee_percent, release_days) VALUES
            ('debito', 1.99, 1),
            ('credito_avista', 3.49, 30),
            ('credito_parcelado', 4.99, 30)");
    }

    $cardRateRuleTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='card_rate_rules'")->fetch();
    if (!$cardRateRuleTable) {
        $pdo->exec('CREATE TABLE card_rate_rules (
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
        )');
    }

    $cardRateRuleCount = (int) ($pdo->query('SELECT COUNT(*) FROM card_rate_rules')->fetchColumn() ?: 0);
    if ($cardRateRuleCount === 0) {
        $pdo->exec("INSERT INTO card_rate_rules (machine_id, brand_id, payment_config_id, fee_percent, release_days)
            SELECT m.id, b.id, p.id, 0.99, 1
            FROM card_machines m, card_brands b, card_payment_configs p
            WHERE m.name='Cielo' AND b.name='Elo' AND p.name='debito'");
        $pdo->exec("INSERT INTO card_rate_rules (machine_id, brand_id, payment_config_id, fee_percent, release_days)
            SELECT m.id, b.id, p.id, 1.33, 1
            FROM card_machines m, card_brands b, card_payment_configs p
            WHERE m.name='PagSeguro' AND b.name='Elo' AND p.name='debito'");
    }

    $saleLocationTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sale_locations'")->fetch();
    if (!$saleLocationTable) {
        $pdo->exec('CREATE TABLE sale_locations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $saleLocationCount = (int) ($pdo->query('SELECT COUNT(*) FROM sale_locations')->fetchColumn() ?: 0);
    if ($saleLocationCount === 0) {
        $pdo->exec("INSERT INTO sale_locations (name) VALUES ('Caixa Loja'), ('Financeiro'), ('Caixa Parafuso')");
    }
}
