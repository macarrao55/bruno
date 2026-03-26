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
    $addPayableColumn($pdo, 'paid_on', 'TEXT');
    $addPayableColumn($pdo, 'payment_method', 'TEXT');
    $addPayableColumn($pdo, 'bank_account_id', 'INTEGER');
    $addPayableColumn($pdo, 'discount', 'REAL NOT NULL DEFAULT 0');
    $addPayableColumn($pdo, 'addition', 'REAL NOT NULL DEFAULT 0');
    $addPayableColumn($pdo, 'late_interest', 'REAL NOT NULL DEFAULT 0');
    $addPayableColumn($pdo, 'paid_amount', 'REAL');

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
}
