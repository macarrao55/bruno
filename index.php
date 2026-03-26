<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();
$module = $_GET['module'] ?? 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePost($pdo, $module);
    header('Location: index.php?module=' . urlencode($module));
    exit;
}

function handlePost(PDO $pdo, string $module): void
{
    switch ($module) {
        case 'fluxo':
            $stmt = $pdo->prepare('INSERT INTO transactions (movement_type, amount, category, subcategory, origin_account, destination_account, description, occurred_on)
                VALUES (:movement_type,:amount,:category,:subcategory,:origin_account,:destination_account,:description,:occurred_on)');
            $stmt->execute([
                ':movement_type' => $_POST['movement_type'],
                ':amount' => (float) $_POST['amount'],
                ':category' => trim($_POST['category']),
                ':subcategory' => trim($_POST['subcategory']),
                ':origin_account' => trim($_POST['origin_account']),
                ':destination_account' => trim($_POST['destination_account']),
                ':description' => trim($_POST['description']),
                ':occurred_on' => $_POST['occurred_on'],
            ]);
            break;

        case 'pagar':
            $action = $_POST['action'] ?? 'create';

            if ($action === 'create') {
                $supplierId = (int) ($_POST['supplier_id'] ?? 0);
                $supplierName = trim((string) ($_POST['supplier_name'] ?? $_POST['supplier']));
                $companyId = (int) ($_POST['company_id'] ?? 0);
                $companyName = '';
                if ($supplierId > 0) {
                    $supplierStmt = $pdo->prepare('SELECT name FROM suppliers WHERE id=:id');
                    $supplierStmt->execute([':id' => $supplierId]);
                    $supplier = $supplierStmt->fetch();
                    $supplierName = (string) ($supplier['name'] ?? $supplierName);
                }
                if ($companyId > 0) {
                    $companyStmt = $pdo->prepare('SELECT name FROM companies WHERE id=:id');
                    $companyStmt->execute([':id' => $companyId]);
                    $company = $companyStmt->fetch();
                    $companyName = (string) ($company['name'] ?? '');
                }
                $stmt = $pdo->prepare('INSERT INTO accounts_payable (company_id, company, supplier_id, supplier, payable_type, boleto_number, due_date, amount, installment, status, reminder_date, notes)
                    VALUES (:company_id,:company,:supplier_id,:supplier,:payable_type,:boleto_number,:due_date,:amount,:installment,:status,:reminder_date,:notes)');
                $stmt->execute([
                    ':company_id' => $companyId > 0 ? $companyId : null,
                    ':company' => $companyName,
                    ':supplier_id' => $supplierId > 0 ? $supplierId : null,
                    ':supplier' => $supplierName,
                    ':payable_type' => trim((string) $_POST['payable_type']),
                    ':boleto_number' => trim((string) $_POST['boleto_number']),
                    ':due_date' => $_POST['due_date'],
                    ':amount' => (float) $_POST['amount'],
                    ':installment' => trim($_POST['installment']),
                    ':status' => $_POST['status'],
                    ':reminder_date' => $_POST['reminder_date'] ?: null,
                    ':notes' => trim($_POST['notes']),
                ]);
            }

            if ($action === 'edit') {
                $supplierId = (int) ($_POST['supplier_id'] ?? 0);
                $supplierName = trim((string) ($_POST['supplier_name'] ?? $_POST['supplier']));
                $companyId = (int) ($_POST['company_id'] ?? 0);
                $companyName = '';
                if ($supplierId > 0) {
                    $supplierStmt = $pdo->prepare('SELECT name FROM suppliers WHERE id=:id');
                    $supplierStmt->execute([':id' => $supplierId]);
                    $supplier = $supplierStmt->fetch();
                    $supplierName = (string) ($supplier['name'] ?? $supplierName);
                }
                if ($companyId > 0) {
                    $companyStmt = $pdo->prepare('SELECT name FROM companies WHERE id=:id');
                    $companyStmt->execute([':id' => $companyId]);
                    $company = $companyStmt->fetch();
                    $companyName = (string) ($company['name'] ?? '');
                }
                $stmt = $pdo->prepare('UPDATE accounts_payable
                    SET company_id=:company_id, company=:company, supplier_id=:supplier_id, supplier=:supplier, payable_type=:payable_type, boleto_number=:boleto_number, due_date=:due_date, amount=:amount, installment=:installment, status=:status, reminder_date=:reminder_date, notes=:notes
                    WHERE id=:id');
                $stmt->execute([
                    ':id' => (int) $_POST['id'],
                    ':company_id' => $companyId > 0 ? $companyId : null,
                    ':company' => $companyName,
                    ':supplier_id' => $supplierId > 0 ? $supplierId : null,
                    ':supplier' => $supplierName,
                    ':payable_type' => trim((string) $_POST['payable_type']),
                    ':boleto_number' => trim((string) $_POST['boleto_number']),
                    ':due_date' => $_POST['due_date'],
                    ':amount' => (float) $_POST['amount'],
                    ':installment' => trim($_POST['installment']),
                    ':status' => $_POST['status'],
                    ':reminder_date' => $_POST['reminder_date'] ?: null,
                    ':notes' => trim($_POST['notes']),
                ]);
            }

            if ($action === 'edit_full') {
                $supplierId = (int) ($_POST['supplier_id'] ?? 0);
                $supplierName = trim((string) ($_POST['supplier_name'] ?? $_POST['supplier']));
                $companyId = (int) ($_POST['company_id'] ?? 0);
                $companyName = '';

                if ($supplierId > 0) {
                    $supplierStmt = $pdo->prepare('SELECT name FROM suppliers WHERE id=:id');
                    $supplierStmt->execute([':id' => $supplierId]);
                    $supplier = $supplierStmt->fetch();
                    $supplierName = (string) ($supplier['name'] ?? $supplierName);
                }

                if ($companyId > 0) {
                    $companyStmt = $pdo->prepare('SELECT name FROM companies WHERE id=:id');
                    $companyStmt->execute([':id' => $companyId]);
                    $company = $companyStmt->fetch();
                    $companyName = (string) ($company['name'] ?? '');
                }

                $discount = (float) ($_POST['discount'] ?? 0);
                $addition = (float) ($_POST['addition'] ?? 0);
                $lateInterest = (float) ($_POST['late_interest'] ?? 0);
                $amount = (float) $_POST['amount'];
                $paidAmount = (float) ($_POST['paid_amount'] ?? 0);
                if ($paidAmount <= 0 && ($_POST['status'] ?? '') === 'pago') {
                    $paidAmount = $amount - $discount + $addition + $lateInterest;
                }

                $stmt = $pdo->prepare('UPDATE accounts_payable
                    SET company_id=:company_id, company=:company, supplier_id=:supplier_id, supplier=:supplier, payable_type=:payable_type, boleto_number=:boleto_number,
                        due_date=:due_date, amount=:amount, installment=:installment, status=:status, reminder_date=:reminder_date, notes=:notes,
                        paid_on=:paid_on, payment_method=:payment_method, bank_account_id=:bank_account_id, discount=:discount, addition=:addition, late_interest=:late_interest, paid_amount=:paid_amount
                    WHERE id=:id');
                $stmt->execute([
                    ':id' => (int) $_POST['id'],
                    ':company_id' => $companyId > 0 ? $companyId : null,
                    ':company' => $companyName,
                    ':supplier_id' => $supplierId > 0 ? $supplierId : null,
                    ':supplier' => $supplierName,
                    ':payable_type' => trim((string) $_POST['payable_type']),
                    ':boleto_number' => trim((string) $_POST['boleto_number']),
                    ':due_date' => $_POST['due_date'],
                    ':amount' => $amount,
                    ':installment' => trim($_POST['installment']),
                    ':status' => $_POST['status'],
                    ':reminder_date' => $_POST['reminder_date'] ?: null,
                    ':notes' => trim($_POST['notes']),
                    ':paid_on' => $_POST['paid_on'] ?: null,
                    ':payment_method' => trim((string) $_POST['payment_method']) ?: null,
                    ':bank_account_id' => ((int) ($_POST['bank_account_id'] ?? 0)) > 0 ? (int) $_POST['bank_account_id'] : null,
                    ':discount' => $discount,
                    ':addition' => $addition,
                    ':late_interest' => $lateInterest,
                    ':paid_amount' => $paidAmount > 0 ? $paidAmount : null,
                ]);
            }

            if ($action === 'settle') {
                $id = (int) $_POST['id'];
                $payable = $pdo->prepare('SELECT * FROM accounts_payable WHERE id=:id');
                $payable->execute([':id' => $id]);
                $item = $payable->fetch();
                if (!$item) {
                    break;
                }

                $discount = (float) $_POST['discount'];
                $addition = (float) $_POST['addition'];
                $lateInterest = (float) $_POST['late_interest'];
                $paidAmount = (float) $item['amount'] - $discount + $addition + $lateInterest;
                $bankAccountId = (int) $_POST['bank_account_id'];

                $stmt = $pdo->prepare('UPDATE accounts_payable
                    SET status=\'pago\', paid_on=:paid_on, payment_method=:payment_method, bank_account_id=:bank_account_id, discount=:discount, addition=:addition, late_interest=:late_interest, paid_amount=:paid_amount
                    WHERE id=:id');
                $stmt->execute([
                    ':id' => $id,
                    ':paid_on' => $_POST['paid_on'],
                    ':payment_method' => trim($_POST['payment_method']),
                    ':bank_account_id' => $bankAccountId > 0 ? $bankAccountId : null,
                    ':discount' => $discount,
                    ':addition' => $addition,
                    ':late_interest' => $lateInterest,
                    ':paid_amount' => $paidAmount,
                ]);

                $description = 'Baixa conta a pagar: ' . $item['supplier'];
                $originAccount = 'caixa';
                $bankName = '';

                if ($bankAccountId > 0) {
                    $bankStmt = $pdo->prepare('SELECT name FROM bank_accounts WHERE id=:id');
                    $bankStmt->execute([':id' => $bankAccountId]);
                    $bank = $bankStmt->fetch();
                    $bankName = (string) ($bank['name'] ?? 'Banco');
                    $originAccount = $bankName;

                    $pdo->prepare('UPDATE bank_accounts SET current_balance = current_balance - :amount WHERE id=:id')
                        ->execute([':amount' => $paidAmount, ':id' => $bankAccountId]);

                    $pdo->prepare('INSERT INTO bank_reconciliation (bank_account_id, movement_date, description, system_amount, bank_amount, reconciled)
                        VALUES (:bank_account_id, :movement_date, :description, :system_amount, :bank_amount, 1)')
                        ->execute([
                            ':bank_account_id' => $bankAccountId,
                            ':movement_date' => $_POST['paid_on'],
                            ':description' => $description . ' (' . $bankName . ')',
                            ':system_amount' => -$paidAmount,
                            ':bank_amount' => -$paidAmount,
                        ]);
                }

                $transaction = $pdo->prepare('INSERT INTO transactions (movement_type, amount, category, subcategory, origin_account, destination_account, description, occurred_on)
                    VALUES (\'saida\', :amount, :category, :subcategory, :origin_account, \'fornecedor\', :description, :occurred_on)');
                $transaction->execute([
                    ':amount' => $paidAmount,
                    ':category' => trim((string) ($_POST['settle_category'] ?: 'contas_a_pagar')),
                    ':subcategory' => trim((string) ($_POST['settle_subcategory'] ?: (string) ($item['company'] ?: 'sem_empresa'))),
                    ':origin_account' => $originAccount,
                    ':description' => $description,
                    ':occurred_on' => $_POST['paid_on'],
                ]);
            }

            if ($action === 'supplier_add') {
                $stmt = $pdo->prepare('INSERT INTO suppliers (name, cnpj, email, contact_number, salesperson, cep, state, city)
                    VALUES (:name, :cnpj, :email, :contact_number, :salesperson, :cep, :state, :city)');
                $stmt->execute([
                    ':name' => trim($_POST['name']),
                    ':cnpj' => trim((string) $_POST['cnpj']),
                    ':email' => trim((string) $_POST['email']),
                    ':contact_number' => trim((string) $_POST['contact_number']),
                    ':salesperson' => trim((string) $_POST['salesperson']),
                    ':cep' => trim((string) $_POST['cep']),
                    ':state' => trim((string) $_POST['state']),
                    ':city' => trim((string) $_POST['city']),
                ]);
            }
            break;

        case 'receber':
            $total = (float) $_POST['amount'];
            $received = (float) $_POST['amount_received'];
            $status = $received === 0.0 ? 'aberto' : ($received < $total ? 'parcial' : 'recebido');
            $stmt = $pdo->prepare('INSERT INTO accounts_receivable (customer, due_date, amount, amount_received, installment, is_credit_sale, status, notes)
                VALUES (:customer,:due_date,:amount,:amount_received,:installment,:is_credit_sale,:status,:notes)');
            $stmt->execute([
                ':customer' => trim($_POST['customer']),
                ':due_date' => $_POST['due_date'],
                ':amount' => $total,
                ':amount_received' => $received,
                ':installment' => trim($_POST['installment']),
                ':is_credit_sale' => isset($_POST['is_credit_sale']) ? 1 : 0,
                ':status' => $status,
                ':notes' => trim($_POST['notes']),
            ]);
            break;

        case 'cartoes':
            $gross = (float) $_POST['gross_value'];
            $fee = (float) $_POST['fee_percent'];
            $net = $gross - ($gross * $fee / 100);
            $stmt = $pdo->prepare('INSERT INTO card_receivables (machine, brand, card_type, fee_percent, gross_value, net_value, sale_date, expected_release_date, received)
                VALUES (:machine,:brand,:card_type,:fee_percent,:gross_value,:net_value,:sale_date,:expected_release_date,:received)');
            $stmt->execute([
                ':machine' => trim($_POST['machine']),
                ':brand' => trim($_POST['brand']),
                ':card_type' => $_POST['card_type'],
                ':fee_percent' => $fee,
                ':gross_value' => $gross,
                ':net_value' => $net,
                ':sale_date' => $_POST['sale_date'],
                ':expected_release_date' => $_POST['expected_release_date'],
                ':received' => isset($_POST['received']) ? 1 : 0,
            ]);
            break;

        case 'cheques':
            $stmt = $pdo->prepare('INSERT INTO checks_control (check_type, customer, bank, check_number, due_date, amount, cleared, compensated, returned)
                VALUES (:check_type,:customer,:bank,:check_number,:due_date,:amount,:cleared,:compensated,:returned)');
            $stmt->execute([
                ':check_type' => $_POST['check_type'],
                ':customer' => trim($_POST['customer']),
                ':bank' => trim($_POST['bank']),
                ':check_number' => trim($_POST['check_number']),
                ':due_date' => $_POST['due_date'],
                ':amount' => (float) $_POST['amount'],
                ':cleared' => isset($_POST['cleared']) ? 1 : 0,
                ':compensated' => isset($_POST['compensated']) ? 1 : 0,
                ':returned' => isset($_POST['returned']) ? 1 : 0,
            ]);
            break;

        case 'conciliacao':
            $stmt = $pdo->prepare('INSERT INTO bank_reconciliation (bank_account_id, movement_date, description, system_amount, bank_amount, reconciled)
                VALUES (:bank_account_id,:movement_date,:description,:system_amount,:bank_amount,:reconciled)');
            $stmt->execute([
                ':bank_account_id' => (int) $_POST['bank_account_id'],
                ':movement_date' => $_POST['movement_date'],
                ':description' => trim($_POST['description']),
                ':system_amount' => (float) $_POST['system_amount'],
                ':bank_amount' => (float) $_POST['bank_amount'],
                ':reconciled' => isset($_POST['reconciled']) ? 1 : 0,
            ]);
            break;

        case 'fechamento':
            $opening = (float) $_POST['opening_amount'];
            $entries = (float) $_POST['total_entries'];
            $exits = (float) $_POST['total_exits'];
            $counted = (float) $_POST['counted_amount'];
            $expected = $opening + $entries - $exits;
            $diff = $counted - $expected;
            $stmt = $pdo->prepare('INSERT INTO cash_closing (opening_date, opening_amount, total_entries, total_exits, counted_amount, cash_difference, notes)
                VALUES (:opening_date,:opening_amount,:total_entries,:total_exits,:counted_amount,:cash_difference,:notes)');
            $stmt->execute([
                ':opening_date' => $_POST['opening_date'],
                ':opening_amount' => $opening,
                ':total_entries' => $entries,
                ':total_exits' => $exits,
                ':counted_amount' => $counted,
                ':cash_difference' => $diff,
                ':notes' => trim($_POST['notes']),
            ]);
            break;

        case 'configuracoes':
            $action = $_POST['action'] ?? '';
            if ($action === 'bank_add') {
                $initial = (float) $_POST['initial_balance'];
                $stmt = $pdo->prepare('INSERT INTO bank_accounts (name, initial_balance, current_balance) VALUES (:name, :initial_balance, :current_balance)');
                $stmt->execute([
                    ':name' => trim($_POST['name']),
                    ':initial_balance' => $initial,
                    ':current_balance' => $initial,
                ]);
            }

            if ($action === 'bank_update') {
                $stmt = $pdo->prepare('UPDATE bank_accounts SET name=:name, initial_balance=:initial_balance, current_balance=:current_balance WHERE id=:id');
                $stmt->execute([
                    ':id' => (int) $_POST['id'],
                    ':name' => trim($_POST['name']),
                    ':initial_balance' => (float) $_POST['initial_balance'],
                    ':current_balance' => (float) $_POST['current_balance'],
                ]);
            }

            if ($action === 'category_add') {
                $stmt = $pdo->prepare('INSERT INTO cashflow_categories (name, parent_id) VALUES (:name, NULL)');
                $stmt->execute([':name' => trim($_POST['name'])]);
            }

            if ($action === 'subcategory_add') {
                $stmt = $pdo->prepare('INSERT INTO cashflow_categories (name, parent_id) VALUES (:name, :parent_id)');
                $stmt->execute([
                    ':name' => trim($_POST['name']),
                    ':parent_id' => (int) $_POST['parent_id'],
                ]);
            }

            if ($action === 'category_update') {
                $stmt = $pdo->prepare('UPDATE cashflow_categories SET name=:name WHERE id=:id');
                $stmt->execute([
                    ':id' => (int) $_POST['id'],
                    ':name' => trim($_POST['name']),
                ]);
            }

            if ($action === 'payment_method_add') {
                $stmt = $pdo->prepare('INSERT INTO payment_methods (name) VALUES (:name)');
                $stmt->execute([':name' => trim($_POST['name'])]);
            }

            if ($action === 'payment_method_update') {
                $stmt = $pdo->prepare('UPDATE payment_methods SET name=:name WHERE id=:id');
                $stmt->execute([
                    ':id' => (int) $_POST['id'],
                    ':name' => trim($_POST['name']),
                ]);
            }

            if ($action === 'payment_method_delete') {
                $stmt = $pdo->prepare('DELETE FROM payment_methods WHERE id=:id');
                $stmt->execute([':id' => (int) $_POST['id']]);
            }

            if ($action === 'company_add') {
                $stmt = $pdo->prepare('INSERT INTO companies (name) VALUES (:name)');
                $stmt->execute([':name' => trim($_POST['name'])]);
            }

            if ($action === 'company_update') {
                $stmt = $pdo->prepare('UPDATE companies SET name=:name WHERE id=:id');
                $stmt->execute([
                    ':id' => (int) $_POST['id'],
                    ':name' => trim($_POST['name']),
                ]);
            }

            if ($action === 'company_delete') {
                $stmt = $pdo->prepare('DELETE FROM companies WHERE id=:id');
                $stmt->execute([':id' => (int) $_POST['id']]);
            }

            if ($action === 'payable_type_add') {
                $stmt = $pdo->prepare('INSERT INTO payable_types (name) VALUES (:name)');
                $stmt->execute([':name' => trim($_POST['name'])]);
            }

            if ($action === 'payable_type_update') {
                $stmt = $pdo->prepare('UPDATE payable_types SET name=:name WHERE id=:id');
                $stmt->execute([
                    ':id' => (int) $_POST['id'],
                    ':name' => trim($_POST['name']),
                ]);
            }

            if ($action === 'payable_type_delete') {
                $stmt = $pdo->prepare('DELETE FROM payable_types WHERE id=:id');
                $stmt->execute([':id' => (int) $_POST['id']]);
            }
            break;
    }
}

function money(float $v): string
{
    return 'R$ ' . number_format($v, 2, ',', '.');
}

function sumValue(PDO $pdo, string $sql, array $params = []): float
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (float) ($stmt->fetchColumn() ?: 0);
}

function fetchAll(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$period = $_GET['period'] ?? '30';
$days = in_array($period, ['7', '30', '90'], true) ? (int) $period : 30;
$periodStart = date('Y-m-d', strtotime("-$days days"));

$cashBalance = sumValue($pdo, "SELECT COALESCE(SUM(CASE WHEN movement_type='entrada' THEN amount ELSE -amount END),0) FROM transactions");
$bankBalance = sumValue($pdo, 'SELECT COALESCE(SUM(current_balance),0) FROM bank_accounts');
$payToday = sumValue($pdo, "SELECT COALESCE(SUM(amount),0) FROM accounts_payable WHERE due_date=:d AND status='aberto'", [':d' => $today]);
$receiveToday = sumValue($pdo, "SELECT COALESCE(SUM(amount-amount_received),0) FROM accounts_receivable WHERE due_date=:d AND status IN ('aberto','parcial')", [':d' => $today]);
$cardsReceive = sumValue($pdo, 'SELECT COALESCE(SUM(net_value),0) FROM card_receivables WHERE received=0');
$checksToCompensate = sumValue($pdo, 'SELECT COALESCE(SUM(amount),0) FROM checks_control WHERE compensated=0 AND returned=0');
$monthEntries = sumValue($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE movement_type='entrada' AND occurred_on>=:m", [':m' => $monthStart]);
$monthExits = sumValue($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE movement_type='saida' AND occurred_on>=:m", [':m' => $monthStart]);
$estimatedProfit = $monthEntries - $monthExits;

$chartRows = fetchAll($pdo, "SELECT occurred_on,
    SUM(CASE WHEN movement_type='entrada' THEN amount ELSE 0 END) entradas,
    SUM(CASE WHEN movement_type='saida' THEN amount ELSE 0 END) saidas
    FROM transactions WHERE occurred_on>=:start GROUP BY occurred_on ORDER BY occurred_on", [':start' => $periodStart]);

$transactionFilter = $_GET['filtro'] ?? 'mes';
$filterStart = match ($transactionFilter) {
    'dia' => date('Y-m-d'),
    'semana' => date('Y-m-d', strtotime('-7 days')),
    'periodo' => $_GET['inicio'] ?? date('Y-m-d', strtotime('-30 days')),
    default => date('Y-m-01')
};
$filterEnd = $transactionFilter === 'periodo' ? ($_GET['fim'] ?? date('Y-m-d')) : date('Y-m-d');
$transactions = fetchAll($pdo, 'SELECT * FROM transactions WHERE occurred_on BETWEEN :s AND :e ORDER BY occurred_on DESC, id DESC', [':s' => $filterStart, ':e' => $filterEnd]);

$payables = fetchAll($pdo, 'SELECT *, CASE WHEN status = "aberto" AND due_date < :today THEN "atrasado" ELSE status END AS display_status FROM accounts_payable ORDER BY due_date ASC', [':today' => $today]);
$suppliers = fetchAll($pdo, 'SELECT * FROM suppliers ORDER BY name');
$paymentMethods = fetchAll($pdo, 'SELECT * FROM payment_methods ORDER BY name');
$companies = fetchAll($pdo, 'SELECT * FROM companies ORDER BY name');
$payableTypes = fetchAll($pdo, 'SELECT * FROM payable_types ORDER BY name');
$supplierAnalysis = fetchAll($pdo, "SELECT
    s.id,
    s.name,
    s.cnpj,
    s.city,
    COUNT(ap.id) AS total_titles,
    COALESCE(SUM(ap.amount), 0) AS total_amount,
    COALESCE(SUM(CASE WHEN ap.status='pago' THEN COALESCE(ap.paid_amount, ap.amount) ELSE 0 END), 0) AS paid_amount,
    COALESCE(SUM(CASE WHEN ap.status='aberto' AND ap.due_date < :today THEN ap.amount ELSE 0 END), 0) AS overdue_amount,
    COALESCE(AVG(CASE WHEN ap.status='pago' AND ap.paid_on IS NOT NULL AND ap.paid_on > ap.due_date
        THEN julianday(ap.paid_on) - julianday(ap.due_date) ELSE NULL END), 0) AS avg_delay_days
    FROM suppliers s
    LEFT JOIN accounts_payable ap ON ap.supplier_id = s.id
    GROUP BY s.id, s.name, s.cnpj, s.city
    ORDER BY overdue_amount DESC, total_amount DESC", [':today' => $today]);

$supplierSummary = [
    'total_suppliers' => (int) count($suppliers),
    'active_suppliers' => (int) array_reduce($supplierAnalysis, fn($c, $r) => $c + ((int) $r['total_titles'] > 0 ? 1 : 0), 0),
    'suppliers_with_overdue' => (int) array_reduce($supplierAnalysis, fn($c, $r) => $c + ((float) $r['overdue_amount'] > 0 ? 1 : 0), 0),
    'total_overdue' => (float) array_reduce($supplierAnalysis, fn($c, $r) => $c + (float) $r['overdue_amount'], 0),
];
$editingPayable = null;
if ($module === 'pagar' && isset($_GET['edit_id'])) {
    $stmtEdit = $pdo->prepare('SELECT * FROM accounts_payable WHERE id=:id');
    $stmtEdit->execute([':id' => (int) $_GET['edit_id']]);
    $editingPayable = $stmtEdit->fetch() ?: null;
}
$receivables = fetchAll($pdo, 'SELECT *, CASE WHEN status IN ("aberto","parcial") AND due_date < :today THEN "atrasado" ELSE status END AS display_status FROM accounts_receivable ORDER BY due_date ASC', [':today' => $today]);
$cards = fetchAll($pdo, 'SELECT * FROM card_receivables ORDER BY sale_date DESC');
$checks = fetchAll($pdo, 'SELECT * FROM checks_control ORDER BY due_date ASC');
$banks = fetchAll($pdo, 'SELECT * FROM bank_accounts ORDER BY name');
$reconciliations = fetchAll($pdo, 'SELECT br.*, ba.name bank_name FROM bank_reconciliation br JOIN bank_accounts ba ON ba.id=br.bank_account_id ORDER BY movement_date DESC');
$closings = fetchAll($pdo, 'SELECT * FROM cash_closing ORDER BY opening_date DESC');

$dre = [
    'receitas' => $monthEntries,
    'custos' => sumValue($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE movement_type='saida' AND category='custos' AND occurred_on>=:m", [':m' => $monthStart]),
    'despesas_fixas' => sumValue($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE movement_type='saida' AND category='despesas_fixas' AND occurred_on>=:m", [':m' => $monthStart]),
    'despesas_variaveis' => sumValue($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE movement_type='saida' AND category='despesas_variaveis' AND occurred_on>=:m", [':m' => $monthStart]),
];
$dre['resultado_operacional'] = $dre['receitas'] - $dre['custos'] - $dre['despesas_fixas'] - $dre['despesas_variaveis'];
$dre['lucro_liquido'] = $dre['resultado_operacional'];

$reportType = $_GET['tipo_relatorio'] ?? 'mensal';
$reportSql = match ($reportType) {
    'diario' => "SELECT occurred_on periodo, SUM(CASE WHEN movement_type='entrada' THEN amount ELSE 0 END) entradas, SUM(CASE WHEN movement_type='saida' THEN amount ELSE 0 END) saidas FROM transactions GROUP BY occurred_on ORDER BY occurred_on DESC LIMIT 31",
    'semanal' => "SELECT strftime('%Y-W%W', occurred_on) periodo, SUM(CASE WHEN movement_type='entrada' THEN amount ELSE 0 END) entradas, SUM(CASE WHEN movement_type='saida' THEN amount ELSE 0 END) saidas FROM transactions GROUP BY periodo ORDER BY periodo DESC LIMIT 12",
    'categoria' => "SELECT category periodo, SUM(CASE WHEN movement_type='entrada' THEN amount ELSE 0 END) entradas, SUM(CASE WHEN movement_type='saida' THEN amount ELSE 0 END) saidas FROM transactions GROUP BY category ORDER BY category",
    'conta' => "SELECT origin_account periodo, SUM(CASE WHEN movement_type='entrada' THEN amount ELSE 0 END) entradas, SUM(CASE WHEN movement_type='saida' THEN amount ELSE 0 END) saidas FROM transactions GROUP BY origin_account ORDER BY origin_account",
    'cliente' => "SELECT customer periodo, SUM(amount) entradas, SUM(amount_received) saidas FROM accounts_receivable GROUP BY customer ORDER BY customer",
    'vendedor' => "SELECT 'N/A' periodo, 0 entradas, 0 saidas",
    'forma' => "SELECT movement_type periodo, SUM(amount) entradas, 0 saidas FROM transactions GROUP BY movement_type",
    default => "SELECT strftime('%Y-%m', occurred_on) periodo, SUM(CASE WHEN movement_type='entrada' THEN amount ELSE 0 END) entradas, SUM(CASE WHEN movement_type='saida' THEN amount ELSE 0 END) saidas FROM transactions GROUP BY periodo ORDER BY periodo DESC LIMIT 12"
};
$reportRows = fetchAll($pdo, $reportSql);
$categories = fetchAll($pdo, 'SELECT id, name FROM cashflow_categories WHERE parent_id IS NULL ORDER BY name');
$subcategories = fetchAll($pdo, 'SELECT c.id, c.name, c.parent_id, p.name AS parent_name FROM cashflow_categories c LEFT JOIN cashflow_categories p ON p.id=c.parent_id WHERE c.parent_id IS NOT NULL ORDER BY p.name, c.name');

?><!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Sistema Financeiro PHP</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header>
    <h2>Sistema Financeiro</h2>
    <nav>
        <a href="?module=dashboard">Dashboard</a>
        <a href="?module=fluxo">Fluxo de Caixa</a>
        <a href="?module=pagar">Contas a Pagar</a>
        <a href="?module=receber">Contas a Receber</a>
        <a href="?module=cartoes">Cartões</a>
        <a href="?module=cheques">Cheques</a>
        <a href="?module=conciliacao">Conciliação Bancária</a>
        <a href="?module=dre">DRE</a>
        <a href="?module=fechamento">Fechamento</a>
        <a href="?module=relatorios">Relatórios</a>
        <a href="?module=fornecedores">Fornecedores</a>
        <a href="?module=configuracoes">Configurações</a>
    </nav>
</header>
<div class="container">
<?php if ($module === 'dashboard'): ?>
    <div class="cards">
        <div class="card"><h4>Saldo em Caixa</h4><p><?= money($cashBalance) ?></p></div>
        <div class="card"><h4>Saldo em Bancos</h4><p><?= money($bankBalance) ?></p></div>
        <div class="card"><h4>Contas a Pagar Hoje</h4><p><?= money($payToday) ?></p></div>
        <div class="card"><h4>Contas a Receber Hoje</h4><p><?= money($receiveToday) ?></p></div>
        <div class="card"><h4>Cartões a Receber</h4><p><?= money($cardsReceive) ?></p></div>
        <div class="card"><h4>Cheques a Compensar</h4><p><?= money($checksToCompensate) ?></p></div>
        <div class="card"><h4>Lucro Estimado do Mês</h4><p><?= money($estimatedProfit) ?></p></div>
    </div>

    <form method="get">
        <input type="hidden" name="module" value="dashboard">
        <label>Gráfico por período:</label>
        <select name="period">
            <option value="7" <?= $period === '7' ? 'selected' : '' ?>>7 dias</option>
            <option value="30" <?= $period === '30' ? 'selected' : '' ?>>30 dias</option>
            <option value="90" <?= $period === '90' ? 'selected' : '' ?>>90 dias</option>
        </select>
        <button>Filtrar</button>
    </form>
    <canvas id="chart" height="100"></canvas>
    <script>
        const labels = <?= json_encode(array_column($chartRows, 'occurred_on')) ?>;
        const entradas = <?= json_encode(array_map('floatval', array_column($chartRows, 'entradas'))) ?>;
        const saidas = <?= json_encode(array_map('floatval', array_column($chartRows, 'saidas'))) ?>;
        new Chart(document.getElementById('chart'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Entradas', data: entradas, borderColor: '#2f9e44' },
                    { label: 'Saídas', data: saidas, borderColor: '#d9480f' }
                ]
            }
        });
    </script>
<?php elseif ($module === 'fluxo'): ?>
    <h3>Fluxo de Caixa</h3>
    <form method="post">
        <select name="movement_type"><option value="entrada">Entrada</option><option value="saida">Saída</option></select>
        <input name="amount" type="number" step="0.01" placeholder="Valor" required>
        <select name="category" required>
            <option value="">Categoria</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= htmlspecialchars($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="subcategory">
            <option value="">Subcategoria</option>
            <?php foreach ($subcategories as $subcategory): ?>
                <option value="<?= htmlspecialchars($subcategory['name']) ?>"><?= htmlspecialchars($subcategory['parent_name'] . ' > ' . $subcategory['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="origin_account">
            <option value="caixa">Caixa</option>
            <?php foreach ($banks as $bank): ?>
                <option value="<?= htmlspecialchars($bank['name']) ?>"><?= htmlspecialchars($bank['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="destination_account">
            <option value="caixa">Caixa</option>
            <?php foreach ($banks as $bank): ?>
                <option value="<?= htmlspecialchars($bank['name']) ?>"><?= htmlspecialchars($bank['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="occurred_on" type="date" value="<?= $today ?>" required>
        <input name="description" placeholder="Histórico">
        <button>Lançar</button>
    </form>
    <form method="get">
        <input type="hidden" name="module" value="fluxo">
        <select name="filtro">
            <option value="dia">Dia</option><option value="semana">Semana</option><option value="mes" selected>Mês</option><option value="periodo">Período</option>
        </select>
        <input type="date" name="inicio" value="<?= htmlspecialchars($_GET['inicio'] ?? '') ?>">
        <input type="date" name="fim" value="<?= htmlspecialchars($_GET['fim'] ?? '') ?>">
        <button>Aplicar</button>
    </form>
    <p class="small">Saldo acumulado do filtro: <?= money(array_reduce($transactions, fn($c, $r) => $c + ($r['movement_type'] === 'entrada' ? $r['amount'] : -$r['amount']), 0.0)) ?></p>
    <table>
        <tr><th>Data</th><th>Tipo</th><th>Valor</th><th>Categoria</th><th>Subcategoria</th><th>Origem</th><th>Destino</th><th>Histórico</th></tr>
        <?php foreach ($transactions as $t): ?>
            <tr><td><?= $t['occurred_on'] ?></td><td><?= $t['movement_type'] ?></td><td><?= money((float) $t['amount']) ?></td><td><?= htmlspecialchars($t['category']) ?></td><td><?= htmlspecialchars((string) $t['subcategory']) ?></td><td><?= htmlspecialchars((string) $t['origin_account']) ?></td><td><?= htmlspecialchars((string) $t['destination_account']) ?></td><td><?= htmlspecialchars((string) $t['description']) ?></td></tr>
        <?php endforeach; ?>
    </table>
<?php elseif ($module === 'pagar'): ?>
    <h3>Contas a Pagar</h3>
    <form method="post">
        <input type="hidden" name="action" value="<?= $editingPayable ? 'edit' : 'create' ?>">
        <?php if ($editingPayable): ?>
            <input type="hidden" name="id" value="<?= $editingPayable['id'] ?>">
        <?php endif; ?>
        <select name="company_id">
            <option value="0">Sem empresa</option>
            <?php foreach ($companies as $company): ?>
                <option value="<?= $company['id'] ?>" <?= (int) ($editingPayable['company_id'] ?? 0) === (int) $company['id'] ? 'selected' : '' ?>><?= htmlspecialchars($company['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="supplier_id">
            <option value="0">Fornecedor avulso</option>
            <?php foreach ($suppliers as $supplier): ?>
                <option value="<?= $supplier['id'] ?>" <?= (int) ($editingPayable['supplier_id'] ?? 0) === (int) $supplier['id'] ? 'selected' : '' ?>><?= htmlspecialchars($supplier['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="supplier_name" placeholder="Nome fornecedor (avulso)" value="<?= htmlspecialchars((string) ($editingPayable['supplier'] ?? '')) ?>">
        <input name="boleto_number" placeholder="Número do boleto" value="<?= htmlspecialchars((string) ($editingPayable['boleto_number'] ?? '')) ?>">
        <select name="payable_type">
            <option value="">Tipo</option>
            <?php foreach ($payableTypes as $type): ?>
                <option value="<?= htmlspecialchars($type['name']) ?>" <?= (string) ($editingPayable['payable_type'] ?? '') === (string) $type['name'] ? 'selected' : '' ?>><?= htmlspecialchars($type['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="due_date" type="date" value="<?= htmlspecialchars((string) ($editingPayable['due_date'] ?? '')) ?>" required>
        <input name="amount" type="number" step="0.01" placeholder="Valor" value="<?= htmlspecialchars((string) ($editingPayable['amount'] ?? '')) ?>" required>
        <input name="installment" placeholder="Parcela" value="<?= htmlspecialchars((string) ($editingPayable['installment'] ?? '')) ?>">
        <?php $currentPayableStatus = (string) ($editingPayable['status'] ?? 'aberto'); ?>
        <select name="status">
            <option value="aberto" <?= $currentPayableStatus === 'aberto' ? 'selected' : '' ?>>aberto</option>
            <option value="pago" <?= $currentPayableStatus === 'pago' ? 'selected' : '' ?>>pago</option>
            <option value="atrasado" <?= $currentPayableStatus === 'atrasado' ? 'selected' : '' ?>>atrasado</option>
        </select>
        <input name="reminder_date" type="date" value="<?= htmlspecialchars((string) ($editingPayable['reminder_date'] ?? '')) ?>" placeholder="Aviso">
        <input name="notes" placeholder="Observações" value="<?= htmlspecialchars((string) ($editingPayable['notes'] ?? '')) ?>">
        <button><?= $editingPayable ? 'Atualizar lançamento' : 'Salvar' ?></button>
        <button type="button" onclick="document.getElementById('supplierModal').showModal()">Cadastro de fornecedores</button>
        <?php if ($editingPayable): ?>
            <a href="?module=pagar">Cancelar edição</a>
        <?php endif; ?>
    </form>
    <table><tr><th>Empresa</th><th>Tipo</th><th>Fornecedor</th><th>Boleto</th><th>Vencimento</th><th>Valor</th><th>Parcela</th><th>Situação</th><th>Aviso</th><th>Ações</th></tr>
        <?php foreach ($payables as $p): ?>
            <tr>
                <td><?= htmlspecialchars((string) $p['company']) ?></td>
                <td><?= htmlspecialchars((string) $p['payable_type']) ?></td>
                <td><?= htmlspecialchars($p['supplier']) ?></td>
                <td><?= htmlspecialchars((string) $p['boleto_number']) ?></td>
                <td><?= $p['due_date'] ?></td>
                <td><?= money((float) $p['amount']) ?></td>
                <td><?= htmlspecialchars((string) $p['installment']) ?></td>
                <td><span class="badge <?= $p['display_status'] ?>"><?= $p['display_status'] ?></span></td>
                <td><?= $p['reminder_date'] ?></td>
                <td>
                    <button
                        type="button"
                        onclick='openEditPayableModal(<?= json_encode([
                            'id' => $p['id'],
                            'company_id' => $p['company_id'],
                            'supplier_id' => $p['supplier_id'],
                            'supplier' => $p['supplier'],
                            'payable_type' => $p['payable_type'],
                            'boleto_number' => $p['boleto_number'],
                            'due_date' => $p['due_date'],
                            'amount' => $p['amount'],
                            'installment' => $p['installment'],
                            'status' => $p['status'],
                            'reminder_date' => $p['reminder_date'],
                            'notes' => $p['notes'],
                            'paid_on' => $p['paid_on'],
                            'payment_method' => $p['payment_method'],
                            'bank_account_id' => $p['bank_account_id'],
                            'discount' => $p['discount'],
                            'addition' => $p['addition'],
                            'late_interest' => $p['late_interest'],
                            'paid_amount' => $p['paid_amount'],
                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                        Editar
                    </button>
                    <?php if ($p['status'] !== 'pago'): ?>
                        <button type="button" onclick="openSettleModal(<?= $p['id'] ?>, <?= (float) $p['amount'] ?>)">Dar baixa</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <dialog id="settleModal">
        <form method="post">
            <input type="hidden" name="action" value="settle">
            <input type="hidden" name="id" id="settle_id">
            <label>Dia do pagamento: <input type="date" name="paid_on" value="<?= $today ?>" required></label><br>
            <label>Forma de pagamento:
                <select name="payment_method" required>
                    <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?= htmlspecialchars($method['name']) ?>"><?= htmlspecialchars($method['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            <label>Banco:
                <select name="bank_account_id">
                    <option value="0">Caixa</option>
                    <?php foreach ($banks as $bank): ?>
                        <option value="<?= $bank['id'] ?>"><?= htmlspecialchars($bank['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            <label>Desconto: <input type="number" step="0.01" name="discount" value="0"></label><br>
            <label>Acrescimento: <input type="number" step="0.01" name="addition" value="0"></label><br>
            <label>Juros de atraso: <input type="number" step="0.01" name="late_interest" value="0"></label><br>
            <label>Categoria:
                <select name="settle_category">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            <label>Subcategoria:
                <select name="settle_subcategory">
                    <option value="">Selecionar</option>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?= htmlspecialchars($subcategory['name']) ?>"><?= htmlspecialchars($subcategory['parent_name'] . ' > ' . $subcategory['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            <p class="small">Valor original: <span id="settle_amount">R$ 0,00</span></p>
            <button>Confirmar baixa</button>
            <button type="button" onclick="document.getElementById('settleModal').close()">Fechar</button>
        </form>
    </dialog>
    <dialog id="editPayableModal">
        <form method="post" id="editPayableForm">
            <input type="hidden" name="action" value="edit_full">
            <input type="hidden" name="id" id="edit_id">
            <h4>Editar dados da conta</h4>
            <select name="company_id" id="edit_company_id">
                <option value="0">Sem empresa</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="supplier_id" id="edit_supplier_id">
                <option value="0">Fornecedor avulso</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="supplier_name" id="edit_supplier_name" placeholder="Fornecedor avulso"><br>
            <input name="boleto_number" id="edit_boleto_number" placeholder="Número do boleto">
            <select name="payable_type" id="edit_payable_type">
                <option value="">Tipo</option>
                <?php foreach ($payableTypes as $type): ?>
                    <option value="<?= htmlspecialchars($type['name']) ?>"><?= htmlspecialchars($type['name']) ?></option>
                <?php endforeach; ?>
            </select><br>
            <input name="due_date" id="edit_due_date" type="date" required>
            <input name="amount" id="edit_amount" type="number" step="0.01" required>
            <input name="installment" id="edit_installment" placeholder="Parcela"><br>
            <select name="status" id="edit_status">
                <option value="aberto">aberto</option>
                <option value="pago">pago</option>
                <option value="atrasado">atrasado</option>
            </select>
            <input name="reminder_date" id="edit_reminder_date" type="date">
            <input name="notes" id="edit_notes" placeholder="Observações"><br>

            <h4>Dados de baixa</h4>
            <input name="paid_on" id="edit_paid_on" type="date">
            <select name="payment_method" id="edit_payment_method">
                <option value="">Forma de pagamento</option>
                <?php foreach ($paymentMethods as $method): ?>
                    <option value="<?= htmlspecialchars($method['name']) ?>"><?= htmlspecialchars($method['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="bank_account_id" id="edit_bank_account_id">
                <option value="0">Caixa</option>
                <?php foreach ($banks as $bank): ?>
                    <option value="<?= $bank['id'] ?>"><?= htmlspecialchars($bank['name']) ?></option>
                <?php endforeach; ?>
            </select><br>
            <input name="discount" id="edit_discount" type="number" step="0.01" placeholder="Desconto">
            <input name="addition" id="edit_addition" type="number" step="0.01" placeholder="Acrescimento">
            <input name="late_interest" id="edit_late_interest" type="number" step="0.01" placeholder="Juros"><br>
            <input name="paid_amount" id="edit_paid_amount" type="number" step="0.01" placeholder="Valor pago">
            <button>Salvar edição</button>
            <button type="button" onclick="document.getElementById('editPayableModal').close()">Fechar</button>
        </form>
    </dialog>
    <dialog id="supplierModal">
        <form method="post">
            <input type="hidden" name="action" value="supplier_add">
            <input name="name" placeholder="Nome do fornecedor" required><br>
            <input name="cnpj" placeholder="CNPJ"><br>
            <input name="email" placeholder="Email"><br>
            <input name="contact_number" placeholder="Número de contato"><br>
            <input name="salesperson" placeholder="Vendedor"><br>
            <input name="cep" placeholder="CEP"><br>
            <input name="state" placeholder="Estado"><br>
            <input name="city" placeholder="Cidade"><br>
            <button>Salvar fornecedor</button>
            <button type="button" onclick="document.getElementById('supplierModal').close()">Fechar</button>
        </form>
    </dialog>
    <script>
        function openSettleModal(id, amount) {
            document.getElementById('settle_id').value = id;
            document.getElementById('settle_amount').textContent = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(amount);
            document.getElementById('settleModal').showModal();
        }

        function openEditPayableModal(data) {
            document.getElementById('edit_id').value = data.id ?? '';
            document.getElementById('edit_company_id').value = data.company_id ?? 0;
            document.getElementById('edit_supplier_id').value = data.supplier_id ?? 0;
            document.getElementById('edit_supplier_name').value = data.supplier ?? '';
            document.getElementById('edit_boleto_number').value = data.boleto_number ?? '';
            document.getElementById('edit_payable_type').value = data.payable_type ?? '';
            document.getElementById('edit_due_date').value = data.due_date ?? '';
            document.getElementById('edit_amount').value = data.amount ?? '';
            document.getElementById('edit_installment').value = data.installment ?? '';
            document.getElementById('edit_status').value = data.status ?? 'aberto';
            document.getElementById('edit_reminder_date').value = data.reminder_date ?? '';
            document.getElementById('edit_notes').value = data.notes ?? '';
            document.getElementById('edit_paid_on').value = data.paid_on ?? '';
            document.getElementById('edit_payment_method').value = data.payment_method ?? '';
            document.getElementById('edit_bank_account_id').value = data.bank_account_id ?? 0;
            document.getElementById('edit_discount').value = data.discount ?? 0;
            document.getElementById('edit_addition').value = data.addition ?? 0;
            document.getElementById('edit_late_interest').value = data.late_interest ?? 0;
            document.getElementById('edit_paid_amount').value = data.paid_amount ?? '';
            document.getElementById('editPayableModal').showModal();
        }
    </script>
<?php elseif ($module === 'receber'): ?>
    <h3>Contas a Receber</h3>
    <form method="post">
        <input name="customer" placeholder="Cliente" required>
        <input name="due_date" type="date" required>
        <input name="amount" type="number" step="0.01" placeholder="Valor total" required>
        <input name="amount_received" type="number" step="0.01" placeholder="Recebido" value="0">
        <input name="installment" placeholder="Parcela">
        <label><input type="checkbox" name="is_credit_sale"> Fiado</label>
        <input name="notes" placeholder="Observações">
        <button>Salvar</button>
    </form>
    <table><tr><th>Cliente</th><th>Vencimento</th><th>Total</th><th>Recebido</th><th>Parcela</th><th>Fiado</th><th>Situação</th></tr>
        <?php foreach ($receivables as $r): ?><tr><td><?= htmlspecialchars($r['customer']) ?></td><td><?= $r['due_date'] ?></td><td><?= money((float) $r['amount']) ?></td><td><?= money((float) $r['amount_received']) ?></td><td><?= htmlspecialchars((string) $r['installment']) ?></td><td><?= $r['is_credit_sale'] ? 'Sim' : 'Não' ?></td><td><span class="badge <?= $r['display_status'] ?>"><?= $r['display_status'] ?></span></td></tr><?php endforeach; ?>
    </table>
<?php elseif ($module === 'cartoes'): ?>
    <h3>Controle de Cartões</h3>
    <form method="post">
        <input name="machine" placeholder="Máquina" required><input name="brand" placeholder="Bandeira" required>
        <select name="card_type"><option value="debito">Débito</option><option value="credito_avista">Crédito à vista</option><option value="credito_parcelado">Crédito parcelado</option></select>
        <input name="fee_percent" type="number" step="0.01" placeholder="Taxa %" required>
        <input name="gross_value" type="number" step="0.01" placeholder="Valor bruto" required>
        <input name="sale_date" type="date" required><input name="expected_release_date" type="date" required>
        <label><input type="checkbox" name="received"> Baixa quando receber</label>
        <button>Salvar</button>
    </form>
    <table><tr><th>Máquina</th><th>Bandeira</th><th>Tipo</th><th>Taxa</th><th>Bruto</th><th>Líquido</th><th>Venda</th><th>Liberação</th><th>Recebido</th></tr>
        <?php foreach ($cards as $c): ?><tr><td><?= htmlspecialchars($c['machine']) ?></td><td><?= htmlspecialchars($c['brand']) ?></td><td><?= $c['card_type'] ?></td><td><?= $c['fee_percent'] ?>%</td><td><?= money((float) $c['gross_value']) ?></td><td><?= money((float) $c['net_value']) ?></td><td><?= $c['sale_date'] ?></td><td><?= $c['expected_release_date'] ?></td><td><?= $c['received'] ? 'Sim' : 'Não' ?></td></tr><?php endforeach; ?>
    </table>
<?php elseif ($module === 'cheques'): ?>
    <h3>Controle de Cheques</h3>
    <form method="post">
        <select name="check_type"><option value="avista">À vista</option><option value="parcelado">Parcelado</option></select>
        <input name="customer" placeholder="Cliente" required><input name="bank" placeholder="Banco" required><input name="check_number" placeholder="Número" required>
        <input name="due_date" type="date" required><input name="amount" type="number" step="0.01" placeholder="Valor" required>
        <label><input type="checkbox" name="cleared"> Baixa</label><label><input type="checkbox" name="compensated"> Compensado</label><label><input type="checkbox" name="returned"> Devolvido</label>
        <button>Salvar</button>
    </form>
    <table><tr><th>Tipo</th><th>Cliente</th><th>Banco</th><th>Número</th><th>Vencimento</th><th>Valor</th><th>Baixa</th><th>Compensado</th><th>Devolvido</th></tr>
        <?php foreach ($checks as $c): ?><tr><td><?= $c['check_type'] ?></td><td><?= htmlspecialchars($c['customer']) ?></td><td><?= htmlspecialchars($c['bank']) ?></td><td><?= htmlspecialchars($c['check_number']) ?></td><td><?= $c['due_date'] ?></td><td><?= money((float) $c['amount']) ?></td><td><?= $c['cleared'] ? 'Sim' : 'Não' ?></td><td><?= $c['compensated'] ? 'Sim' : 'Não' ?></td><td><?= $c['returned'] ? 'Sim' : 'Não' ?></td></tr><?php endforeach; ?>
    </table>
<?php elseif ($module === 'conciliacao'): ?>
    <h3>Conciliação Bancária</h3>
    <form method="post">
        <select name="bank_account_id"><?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?></select>
        <input name="movement_date" type="date" required>
        <input name="description" placeholder="Descrição">
        <input name="system_amount" type="number" step="0.01" placeholder="Sistema" required>
        <input name="bank_amount" type="number" step="0.01" placeholder="Banco" required>
        <label><input type="checkbox" name="reconciled"> Conciliado</label>
        <button>Salvar</button>
    </form>
    <table><tr><th>Conta</th><th>Data</th><th>Descrição</th><th>Sistema</th><th>Banco</th><th>Status</th></tr>
        <?php foreach ($reconciliations as $r): ?><tr><td><?= htmlspecialchars($r['bank_name']) ?></td><td><?= $r['movement_date'] ?></td><td><?= htmlspecialchars((string) $r['description']) ?></td><td><?= money((float) $r['system_amount']) ?></td><td><?= money((float) $r['bank_amount']) ?></td><td><?= $r['reconciled'] ? 'Conciliado' : 'Não conciliado' ?></td></tr><?php endforeach; ?>
    </table>
<?php elseif ($module === 'dre'): ?>
    <h3>DRE Gerencial</h3>
    <p class="small">Visão mensal (<?= date('m/Y') ?>) - para visão anual, agregue por mês nos relatórios.</p>
    <table>
        <tr><th>Linha</th><th>Valor</th></tr>
        <tr><td>Receitas</td><td><?= money($dre['receitas']) ?></td></tr>
        <tr><td>Custos</td><td><?= money($dre['custos']) ?></td></tr>
        <tr><td>Despesas Fixas</td><td><?= money($dre['despesas_fixas']) ?></td></tr>
        <tr><td>Despesas Variáveis</td><td><?= money($dre['despesas_variaveis']) ?></td></tr>
        <tr><td>Resultado Operacional</td><td><?= money($dre['resultado_operacional']) ?></td></tr>
        <tr><td>Lucro Líquido</td><td><?= money($dre['lucro_liquido']) ?></td></tr>
    </table>
<?php elseif ($module === 'fechamento'): ?>
    <h3>Fechamento de Caixa</h3>
    <form method="post">
        <input name="opening_date" type="date" required>
        <input name="opening_amount" type="number" step="0.01" placeholder="Abertura do caixa" required>
        <input name="total_entries" type="number" step="0.01" placeholder="Entradas do dia" required>
        <input name="total_exits" type="number" step="0.01" placeholder="Saídas do dia" required>
        <input name="counted_amount" type="number" step="0.01" placeholder="Valor conferido" required>
        <textarea name="notes" placeholder="Observações"></textarea>
        <button>Fechar</button>
    </form>
    <table><tr><th>Data</th><th>Abertura</th><th>Entradas</th><th>Saídas</th><th>Conferido</th><th>Diferença</th><th>Obs.</th></tr>
        <?php foreach ($closings as $f): ?><tr><td><?= $f['opening_date'] ?></td><td><?= money((float) $f['opening_amount']) ?></td><td><?= money((float) $f['total_entries']) ?></td><td><?= money((float) $f['total_exits']) ?></td><td><?= money((float) $f['counted_amount']) ?></td><td><?= money((float) $f['cash_difference']) ?></td><td><?= htmlspecialchars((string) $f['notes']) ?></td></tr><?php endforeach; ?>
    </table>
<?php elseif ($module === 'relatorios'): ?>
    <h3>Relatórios</h3>
    <form method="get">
        <input type="hidden" name="module" value="relatorios">
        <select name="tipo_relatorio">
            <option value="diario">Diário</option><option value="semanal">Semanal</option><option value="mensal">Mensal</option>
            <option value="categoria">Por categoria</option><option value="conta">Por conta</option><option value="cliente">Por cliente</option>
            <option value="vendedor">Por vendedor</option><option value="forma">Por forma de pagamento</option>
        </select>
        <button>Gerar</button>
    </form>
    <table><tr><th>Agrupamento</th><th>Entradas</th><th>Saídas/Recebido</th><th>Saldo</th></tr>
        <?php foreach ($reportRows as $r): $saldo = (float)$r['entradas'] - (float)$r['saidas']; ?>
        <tr><td><?= htmlspecialchars((string)$r['periodo']) ?></td><td><?= money((float)$r['entradas']) ?></td><td><?= money((float)$r['saidas']) ?></td><td><?= money($saldo) ?></td></tr>
        <?php endforeach; ?>
    </table>
<?php elseif ($module === 'fornecedores'): ?>
    <h3>Fornecedores</h3>
    <div class="cards">
        <div class="card"><h4>Fornecedores cadastrados</h4><p><?= $supplierSummary['total_suppliers'] ?></p></div>
        <div class="card"><h4>Fornecedores ativos</h4><p><?= $supplierSummary['active_suppliers'] ?></p></div>
        <div class="card"><h4>Com títulos em atraso</h4><p><?= $supplierSummary['suppliers_with_overdue'] ?></p></div>
        <div class="card"><h4>Total em atraso</h4><p><?= money($supplierSummary['total_overdue']) ?></p></div>
    </div>
    <p class="small">Análise prévia baseada em histórico financeiro de contas a pagar por fornecedor.</p>
    <table>
        <tr><th>Fornecedor</th><th>CNPJ</th><th>Cidade</th><th>Títulos</th><th>Volume total</th><th>Pago</th><th>Em atraso</th><th>Média atraso (dias)</th><th>Risco prévio</th></tr>
        <?php foreach ($supplierAnalysis as $row): ?>
            <?php
                $avgDelay = (float) $row['avg_delay_days'];
                $overdue = (float) $row['overdue_amount'];
                $risk = 'Baixo';
                if ($overdue > 0 || $avgDelay > 7) {
                    $risk = 'Médio';
                }
                if ($overdue > 5000 || $avgDelay > 15) {
                    $risk = 'Alto';
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars((string) $row['cnpj']) ?></td>
                <td><?= htmlspecialchars((string) $row['city']) ?></td>
                <td><?= (int) $row['total_titles'] ?></td>
                <td><?= money((float) $row['total_amount']) ?></td>
                <td><?= money((float) $row['paid_amount']) ?></td>
                <td><?= money($overdue) ?></td>
                <td><?= number_format($avgDelay, 1, ',', '.') ?></td>
                <td><span class="badge"><?= $risk ?></span></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php elseif ($module === 'configuracoes'): ?>
    <h3>Configurações</h3>

    <h4>Bancos e saldos iniciais</h4>
    <form method="post">
        <input type="hidden" name="action" value="bank_add">
        <input name="name" placeholder="Nome do banco" required>
        <input name="initial_balance" type="number" step="0.01" placeholder="Saldo inicial" required>
        <button>Adicionar banco</button>
    </form>
    <table>
        <tr><th>Banco</th><th>Saldo Inicial</th><th>Saldo Atual</th><th>Salvar</th></tr>
        <?php foreach ($banks as $b): ?>
            <tr>
                <td>
                    <form method="post">
                        <input type="hidden" name="action" value="bank_update">
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars($b['name']) ?>" required>
                </td>
                <td><input name="initial_balance" type="number" step="0.01" value="<?= $b['initial_balance'] ?>"></td>
                <td><input name="current_balance" type="number" step="0.01" value="<?= $b['current_balance'] ?>"></td>
                <td><button>Atualizar</button></form></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Empresas</h4>
    <form method="post">
        <input type="hidden" name="action" value="company_add">
        <input name="name" placeholder="Nova empresa" required>
        <button>Adicionar empresa</button>
    </form>
    <table>
        <tr><th>Empresa</th><th>Editar</th><th>Excluir</th></tr>
        <?php foreach ($companies as $company): ?>
            <tr>
                <td><?= htmlspecialchars($company['name']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="company_update">
                        <input type="hidden" name="id" value="<?= $company['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars($company['name']) ?>" required>
                        <button>Salvar</button>
                    </form>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('Excluir empresa?')" style="display:inline;">
                        <input type="hidden" name="action" value="company_delete">
                        <input type="hidden" name="id" value="<?= $company['id'] ?>">
                        <button>Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Tipos de conta a pagar</h4>
    <form method="post">
        <input type="hidden" name="action" value="payable_type_add">
        <input name="name" placeholder="Novo tipo" required>
        <button>Adicionar tipo</button>
    </form>
    <table>
        <tr><th>Tipo</th><th>Editar</th><th>Excluir</th></tr>
        <?php foreach ($payableTypes as $type): ?>
            <tr>
                <td><?= htmlspecialchars($type['name']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="payable_type_update">
                        <input type="hidden" name="id" value="<?= $type['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars($type['name']) ?>" required>
                        <button>Salvar</button>
                    </form>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('Excluir tipo?')" style="display:inline;">
                        <input type="hidden" name="action" value="payable_type_delete">
                        <input type="hidden" name="id" value="<?= $type['id'] ?>">
                        <button>Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Categorias do fluxo de caixa</h4>
    <form method="post">
        <input type="hidden" name="action" value="category_add">
        <input name="name" placeholder="Nova categoria" required>
        <button>Adicionar categoria</button>
    </form>
    <table>
        <tr><th>Categoria</th><th>Salvar</th></tr>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td>
                    <form method="post">
                        <input type="hidden" name="action" value="category_update">
                        <input type="hidden" name="id" value="<?= $category['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
                </td>
                <td><button>Atualizar</button></form></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Subcategorias do fluxo de caixa</h4>
    <form method="post">
        <input type="hidden" name="action" value="subcategory_add">
        <input name="name" placeholder="Nova subcategoria" required>
        <select name="parent_id" required>
            <option value="">Categoria pai</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button>Adicionar subcategoria</button>
    </form>
    <table>
        <tr><th>Categoria</th><th>Subcategoria</th></tr>
        <?php foreach ($subcategories as $subcategory): ?>
            <tr>
                <td><?= htmlspecialchars($subcategory['parent_name']) ?></td>
                <td><?= htmlspecialchars($subcategory['name']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Formas de pagamento</h4>
    <form method="post">
        <input type="hidden" name="action" value="payment_method_add">
        <input name="name" placeholder="Nova forma de pagamento" required>
        <button>Adicionar forma</button>
    </form>
    <table>
        <tr><th>Forma</th><th>Editar</th><th>Excluir</th></tr>
        <?php foreach ($paymentMethods as $method): ?>
            <tr>
                <td><?= htmlspecialchars($method['name']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="payment_method_update">
                        <input type="hidden" name="id" value="<?= $method['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars($method['name']) ?>" required>
                        <button>Salvar</button>
                    </form>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('Excluir forma de pagamento?')" style="display:inline;">
                        <input type="hidden" name="action" value="payment_method_delete">
                        <input type="hidden" name="id" value="<?= $method['id'] ?>">
                        <button>Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</div>
</body>
</html>
