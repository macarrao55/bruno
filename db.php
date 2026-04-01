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
    $hasLaunchEnabled = false;
    $hasTransferEnabled = false;

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'initial_balance') {
            $hasInitialBalance = true;
        }
        if (($column['name'] ?? '') === 'launch_enabled') {
            $hasLaunchEnabled = true;
        }
        if (($column['name'] ?? '') === 'transfer_enabled') {
            $hasTransferEnabled = true;
        }
    }

    if (!$hasInitialBalance) {
        $pdo->exec('ALTER TABLE bank_accounts ADD COLUMN initial_balance REAL NOT NULL DEFAULT 0');
        $pdo->exec('UPDATE bank_accounts SET initial_balance = current_balance WHERE initial_balance = 0');
    }
    if (!$hasLaunchEnabled) {
        $pdo->exec('ALTER TABLE bank_accounts ADD COLUMN launch_enabled INTEGER NOT NULL DEFAULT 1');
        $pdo->exec('UPDATE bank_accounts SET launch_enabled = 1 WHERE launch_enabled IS NULL');
    }
    if (!$hasTransferEnabled) {
        $pdo->exec('ALTER TABLE bank_accounts ADD COLUMN transfer_enabled INTEGER NOT NULL DEFAULT 1');
        $pdo->exec('UPDATE bank_accounts SET transfer_enabled = 1 WHERE transfer_enabled IS NULL');
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
    $addPayableColumn($pdo, 'created_at', 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP');
    $pdo->exec("UPDATE accounts_payable SET created_at = COALESCE(created_at, datetime('now'))");

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

    $checkColumns = $pdo->query("PRAGMA table_info(checks_control)")->fetchAll();
    $checkColumnNames = array_map(static fn(array $column): string => (string) ($column['name'] ?? ''), $checkColumns);
    $addCheckColumn = static function (PDO $conn, string $name, string $type) use ($checkColumnNames): void {
        if (!in_array($name, $checkColumnNames, true)) {
            $conn->exec("ALTER TABLE checks_control ADD COLUMN $name $type");
        }
    };
    $addCheckColumn($pdo, 'check_date', 'TEXT');
    $addCheckColumn($pdo, 'notes', 'TEXT');
    $pdo->exec("UPDATE checks_control SET check_date = COALESCE(check_date, due_date)");

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

    $frontCashRegistersTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='front_cash_registers'")->fetch();
    if (!$frontCashRegistersTable) {
        $pdo->exec('CREATE TABLE front_cash_registers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }
    $frontCashRegisterCount = (int) ($pdo->query('SELECT COUNT(*) FROM front_cash_registers')->fetchColumn() ?: 0);
    if ($frontCashRegisterCount === 0) {
        $pdo->exec("INSERT INTO front_cash_registers (name) VALUES ('Caixa 1'), ('Caixa 2')");
    }

    $frontCashSalesTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='front_cash_sales'")->fetch();
    if (!$frontCashSalesTable) {
        $pdo->exec('CREATE TABLE front_cash_sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sale_date TEXT NOT NULL,
            cash_register TEXT NOT NULL,
            sale_location TEXT NOT NULL,
            payment_method TEXT NOT NULL,
            amount REAL NOT NULL,
            transaction_id INTEGER,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL
        )');
    } else {
        $frontCashSalesColumns = $pdo->query("PRAGMA table_info(front_cash_sales)")->fetchAll();
        $frontCashSalesColumnNames = array_map(static fn(array $column): string => (string) ($column['name'] ?? ''), $frontCashSalesColumns);
        if (!in_array('transaction_id', $frontCashSalesColumnNames, true)) {
            $pdo->exec('ALTER TABLE front_cash_sales ADD COLUMN transaction_id INTEGER');
        }
    }

    $frontCashVendorsTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='front_cash_vendors'")->fetch();
    if (!$frontCashVendorsTable) {
        $pdo->exec('CREATE TABLE front_cash_vendors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }
    $frontCashVendorCount = (int) ($pdo->query('SELECT COUNT(*) FROM front_cash_vendors')->fetchColumn() ?: 0);
    if ($frontCashVendorCount === 0) {
        $pdo->exec("INSERT INTO front_cash_vendors (name) VALUES ('Vendedor 1'), ('Vendedor 2')");
    }

    $frontCashGasSalesTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='front_cash_gas_sales'")->fetch();
    if (!$frontCashGasSalesTable) {
        $pdo->exec('CREATE TABLE front_cash_gas_sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sale_date TEXT NOT NULL,
            seller TEXT NOT NULL,
            qty_refill INTEGER NOT NULL DEFAULT 0,
            qty_full INTEGER NOT NULL DEFAULT 0,
            delivery_type TEXT NOT NULL CHECK (delivery_type IN (\'retirada\', \'entrega\')),
            payment_method TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $dreConfigTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='dre_config'")->fetch();
    if (!$dreConfigTable) {
        $pdo->exec('CREATE TABLE dre_config (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            month_ref TEXT NOT NULL UNIQUE,
            sales_taxes REAL NOT NULL DEFAULT 0,
            inventory_initial REAL NOT NULL DEFAULT 0,
            purchases REAL NOT NULL DEFAULT 0,
            purchase_freight REAL NOT NULL DEFAULT 0,
            inventory_final REAL NOT NULL DEFAULT 0,
            sales_commission REAL NOT NULL DEFAULT 0,
            extra_card_fees REAL NOT NULL DEFAULT 0,
            delivery_freight REAL NOT NULL DEFAULT 0,
            packaging REAL NOT NULL DEFAULT 0,
            payroll REAL NOT NULL DEFAULT 0,
            rent REAL NOT NULL DEFAULT 0,
            electricity REAL NOT NULL DEFAULT 0,
            water_internet REAL NOT NULL DEFAULT 0,
            software REAL NOT NULL DEFAULT 0,
            accounting REAL NOT NULL DEFAULT 0,
            loan_interest REAL NOT NULL DEFAULT 0,
            late_interest REAL NOT NULL DEFAULT 0,
            card_anticipation REAL NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $customerReceiptTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='customer_receipts'")->fetch();
    if (!$customerReceiptTable) {
        $pdo->exec('CREATE TABLE customer_receipts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            receipt_date TEXT NOT NULL,
            customer_name TEXT NOT NULL,
            total_amount REAL NOT NULL,
            discount REAL NOT NULL DEFAULT 0,
            interest REAL NOT NULL DEFAULT 0,
            net_amount REAL NOT NULL,
            payment_method TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $creditSalesTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='credit_sales_totals'")->fetch();
    if (!$creditSalesTable) {
        $pdo->exec('CREATE TABLE credit_sales_totals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sale_date TEXT NOT NULL,
            sale_location TEXT,
            total_amount REAL NOT NULL,
            return_on_credit REAL NOT NULL DEFAULT 0,
            return_exchange_credit REAL NOT NULL DEFAULT 0,
            net_amount REAL NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    } else {
        $creditSalesColumns = $pdo->query("PRAGMA table_info(credit_sales_totals)")->fetchAll();
        $creditSalesColumnNames = array_map(static fn(array $column): string => (string) ($column['name'] ?? ''), $creditSalesColumns);
        if (!in_array('sale_location', $creditSalesColumnNames, true)) {
            $pdo->exec('ALTER TABLE credit_sales_totals ADD COLUMN sale_location TEXT');
        }
    }

    $overdueCustomersTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='overdue_customers'")->fetch();
    if (!$overdueCustomersTable) {
        $pdo->exec('CREATE TABLE overdue_customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            collection_entry_date TEXT NOT NULL,
            customer_name TEXT NOT NULL,
            amount REAL NOT NULL,
            status TEXT NOT NULL CHECK (status IN (\'vencido\', \'spc\', \'outra\')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $financeExpensesTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='finance_expenses'")->fetch();
    if (!$financeExpensesTable) {
        $pdo->exec('CREATE TABLE finance_expenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            expense_date TEXT NOT NULL,
            name TEXT NOT NULL,
            amount REAL NOT NULL,
            payment_method TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    $employeesTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='employees'")->fetch();
    if (!$employeesTable) {
        $pdo->exec('CREATE TABLE employees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            role TEXT NOT NULL,
            profile_data TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    } else {
        $employeesColumns = $pdo->query("PRAGMA table_info(employees)")->fetchAll();
        $employeesColumnNames = array_map(static fn(array $column): string => (string) ($column['name'] ?? ''), $employeesColumns);
        if (!in_array('profile_data', $employeesColumnNames, true)) {
            $pdo->exec('ALTER TABLE employees ADD COLUMN profile_data TEXT');
        }
    }

    $employeeDebtsTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='employee_debts'")->fetch();
    if (!$employeeDebtsTable) {
        $pdo->exec('CREATE TABLE employee_debts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            debt_date TEXT NOT NULL,
            due_date TEXT,
            installment_label TEXT NOT NULL DEFAULT \'1/1\',
            installment_group TEXT,
            debt_type TEXT NOT NULL DEFAULT \'outras_despesas\' CHECK (debt_type IN (\'vale\', \'debito\', \'compra_loja\', \'emprestimo\', \'outras_despesas\')),
            description TEXT,
            amount REAL NOT NULL,
            status TEXT NOT NULL DEFAULT \'aberto\' CHECK (status IN (\'aberto\', \'quitado\')),
            paid_on TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )');
    } else {
        $employeeDebtsColumns = $pdo->query("PRAGMA table_info(employee_debts)")->fetchAll();
        $employeeDebtsColumnNames = array_map(static fn(array $column): string => (string) ($column['name'] ?? ''), $employeeDebtsColumns);
        if (!in_array('due_date', $employeeDebtsColumnNames, true)) {
            $pdo->exec('ALTER TABLE employee_debts ADD COLUMN due_date TEXT');
        }
        if (!in_array('installment_label', $employeeDebtsColumnNames, true)) {
            $pdo->exec("ALTER TABLE employee_debts ADD COLUMN installment_label TEXT NOT NULL DEFAULT '1/1'");
        }
        if (!in_array('installment_group', $employeeDebtsColumnNames, true)) {
            $pdo->exec('ALTER TABLE employee_debts ADD COLUMN installment_group TEXT');
        }
        if (!in_array('debt_type', $employeeDebtsColumnNames, true)) {
            $pdo->exec("ALTER TABLE employee_debts ADD COLUMN debt_type TEXT NOT NULL DEFAULT 'outras_despesas'");
        }
        if (!in_array('paid_on', $employeeDebtsColumnNames, true)) {
            $pdo->exec('ALTER TABLE employee_debts ADD COLUMN paid_on TEXT');
        }
    }

    $employeeAttendanceTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='employee_attendance_logs'")->fetch();
    if (!$employeeAttendanceTable) {
        $pdo->exec('CREATE TABLE employee_attendance_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            work_date TEXT NOT NULL,
            check_in_time TEXT,
            lunch_out_time TEXT,
            lunch_in_time TEXT,
            check_out_time TEXT,
            notes TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )');
    }

    $employeePerformanceTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='employee_performance_reviews'")->fetch();
    if (!$employeePerformanceTable) {
        $pdo->exec('CREATE TABLE employee_performance_reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            review_date TEXT NOT NULL,
            score INTEGER NOT NULL CHECK (score BETWEEN 1 AND 5),
            strengths TEXT,
            improvements TEXT,
            notes TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )');
    }

    $employeeOccurrencesTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='employee_occurrences'")->fetch();
    if (!$employeeOccurrencesTable) {
        $pdo->exec('CREATE TABLE employee_occurrences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            occurrence_date TEXT NOT NULL,
            occurrence_type TEXT NOT NULL CHECK (occurrence_type IN (\'falta\', \'atraso\')),
            reason TEXT,
            has_medical_certificate INTEGER NOT NULL DEFAULT 0,
            notes TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )');
    }

    $employeeMonthlyCostsTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='employee_monthly_costs'")->fetch();
    if (!$employeeMonthlyCostsTable) {
        $pdo->exec('CREATE TABLE employee_monthly_costs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            reference_month TEXT NOT NULL,
            base_salary REAL NOT NULL,
            inss_patronal REAL NOT NULL DEFAULT 0,
            inss_common REAL NOT NULL,
            fgts REAL NOT NULL,
            thirteenth_provision REAL NOT NULL,
            vacation_provision REAL NOT NULL,
            extra_expense_1 REAL NOT NULL DEFAULT 0,
            extra_expense_2 REAL NOT NULL DEFAULT 0,
            sales_commission REAL NOT NULL DEFAULT 0,
            gas_commission REAL NOT NULL DEFAULT 0,
            total_monthly_cost REAL NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )');
    } else {
        $employeeMonthlyCostsColumns = $pdo->query("PRAGMA table_info(employee_monthly_costs)")->fetchAll();
        $employeeMonthlyCostsColumnNames = array_map(static fn(array $column): string => (string) ($column['name'] ?? ''), $employeeMonthlyCostsColumns);
        if (!in_array('inss_common', $employeeMonthlyCostsColumnNames, true)) {
            $pdo->exec('ALTER TABLE employee_monthly_costs ADD COLUMN inss_common REAL NOT NULL DEFAULT 0');
            $pdo->exec('UPDATE employee_monthly_costs SET inss_common = COALESCE(inss_patronal, 0)');
        }
        if (!in_array('inss_patronal', $employeeMonthlyCostsColumnNames, true)) {
            $pdo->exec('ALTER TABLE employee_monthly_costs ADD COLUMN inss_patronal REAL NOT NULL DEFAULT 0');
            $pdo->exec('UPDATE employee_monthly_costs SET inss_patronal = COALESCE(inss_common, 0)');
        }
        if (!in_array('extra_expense_1', $employeeMonthlyCostsColumnNames, true)) {
            $pdo->exec('ALTER TABLE employee_monthly_costs ADD COLUMN extra_expense_1 REAL NOT NULL DEFAULT 0');
        }
        if (!in_array('extra_expense_2', $employeeMonthlyCostsColumnNames, true)) {
            $pdo->exec('ALTER TABLE employee_monthly_costs ADD COLUMN extra_expense_2 REAL NOT NULL DEFAULT 0');
        }
        if (!in_array('sales_commission', $employeeMonthlyCostsColumnNames, true)) {
            $pdo->exec('ALTER TABLE employee_monthly_costs ADD COLUMN sales_commission REAL NOT NULL DEFAULT 0');
        }
        if (!in_array('gas_commission', $employeeMonthlyCostsColumnNames, true)) {
            $pdo->exec('ALTER TABLE employee_monthly_costs ADD COLUMN gas_commission REAL NOT NULL DEFAULT 0');
        }
    }

    $vehiclesTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='vehicles'")->fetch();
    if (!$vehiclesTable) {
        $pdo->exec('CREATE TABLE vehicles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            plate TEXT,
            model TEXT,
            year TEXT,
            vehicle_value REAL NOT NULL DEFAULT 0,
            depreciation_percent REAL NOT NULL DEFAULT 0,
            vehicle_notes TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }
    $vehicleColumns = $pdo->query("PRAGMA table_info(vehicles)")->fetchAll();
    $vehicleColumnNames = array_map(static fn(array $column): string => (string) ($column['name'] ?? ''), $vehicleColumns);
    $addVehicleColumn = static function (PDO $conn, string $name, string $type) use ($vehicleColumnNames): void {
        if (!in_array($name, $vehicleColumnNames, true)) {
            $conn->exec("ALTER TABLE vehicles ADD COLUMN $name $type");
        }
    };
    $addVehicleColumn($pdo, 'vehicle_value', 'REAL NOT NULL DEFAULT 0');
    $addVehicleColumn($pdo, 'depreciation_percent', 'REAL NOT NULL DEFAULT 0');
    $addVehicleColumn($pdo, 'vehicle_notes', 'TEXT');

    $vehicleExpensesTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='vehicle_expenses'")->fetch();
    if (!$vehicleExpensesTable) {
        $pdo->exec('CREATE TABLE vehicle_expenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vehicle_id INTEGER NOT NULL,
            expense_date TEXT NOT NULL,
            expense_type TEXT NOT NULL CHECK (expense_type IN (\'despesa\', \'manutencao\', \'abastecimento\')),
            expense_subtype TEXT,
            description TEXT,
            km_current REAL,
            liters REAL,
            next_oil_km REAL,
            next_review_km REAL,
            amount REAL NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
        )');
    }
    $vehicleExpenseColumns = $pdo->query("PRAGMA table_info(vehicle_expenses)")->fetchAll();
    $vehicleExpenseColumnNames = array_map(static fn(array $column): string => (string) ($column['name'] ?? ''), $vehicleExpenseColumns);
    if (!in_array('km_current', $vehicleExpenseColumnNames, true)) {
        $pdo->exec('ALTER TABLE vehicle_expenses ADD COLUMN km_current REAL');
    }
    if (!in_array('expense_subtype', $vehicleExpenseColumnNames, true)) {
        $pdo->exec('ALTER TABLE vehicle_expenses ADD COLUMN expense_subtype TEXT');
    }
    if (!in_array('liters', $vehicleExpenseColumnNames, true)) {
        $pdo->exec('ALTER TABLE vehicle_expenses ADD COLUMN liters REAL');
    }
    if (!in_array('next_oil_km', $vehicleExpenseColumnNames, true)) {
        $pdo->exec('ALTER TABLE vehicle_expenses ADD COLUMN next_oil_km REAL');
    }
    if (!in_array('next_review_km', $vehicleExpenseColumnNames, true)) {
        $pdo->exec('ALTER TABLE vehicle_expenses ADD COLUMN next_review_km REAL');
    }

}
