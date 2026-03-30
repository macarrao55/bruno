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

            if ($action === 'create_installments') {
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

                $installmentsCount = max(1, (int) ($_POST['installments_count'] ?? 1));
                $payload = json_decode((string) ($_POST['installments_payload'] ?? '[]'), true);
                if (!is_array($payload)) {
                    $payload = [];
                }
                if ($installmentsCount < 3 || count($payload) !== $installmentsCount) {
                    break;
                }

                $totalAmount = (float) $_POST['amount'];
                $baseAmount = round($totalAmount / $installmentsCount, 2);
                $sumAmounts = 0.0;

                $stmt = $pdo->prepare('INSERT INTO accounts_payable (company_id, company, supplier_id, supplier, payable_type, boleto_number, due_date, amount, installment, status, reminder_date, notes)
                    VALUES (:company_id,:company,:supplier_id,:supplier,:payable_type,:boleto_number,:due_date,:amount,:installment,:status,:reminder_date,:notes)');

                for ($i = 0; $i < $installmentsCount; $i++) {
                    $item = is_array($payload[$i] ?? null) ? $payload[$i] : [];
                    $amount = $i === $installmentsCount - 1 ? round($totalAmount - $sumAmounts, 2) : $baseAmount;
                    $sumAmounts += $amount;

                    $stmt->execute([
                        ':company_id' => $companyId > 0 ? $companyId : null,
                        ':company' => $companyName,
                        ':supplier_id' => $supplierId > 0 ? $supplierId : null,
                        ':supplier' => $supplierName,
                        ':payable_type' => trim((string) $_POST['payable_type']),
                        ':boleto_number' => trim((string) ($item['boleto_number'] ?? $_POST['boleto_number'] ?? '')),
                        ':due_date' => (string) ($item['due_date'] ?? $_POST['due_date']),
                        ':amount' => $amount,
                        ':installment' => ($i + 1) . '/' . $installmentsCount,
                        ':status' => $_POST['status'],
                        ':reminder_date' => $_POST['reminder_date'] ?: null,
                        ':notes' => trim((string) ($item['notes'] ?? $_POST['notes'] ?? '')),
                    ]);
                }
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

            if ($action === 'delete') {
                $id = (int) $_POST['id'];
                $deleteCashflow = isset($_POST['delete_cashflow']) && (int) $_POST['delete_cashflow'] === 1;

                $payableStmt = $pdo->prepare('SELECT * FROM accounts_payable WHERE id=:id');
                $payableStmt->execute([':id' => $id]);
                $payable = $payableStmt->fetch();
                if ($payable) {
                    $pdo->prepare('DELETE FROM accounts_payable WHERE id=:id')
                        ->execute([':id' => $id]);

                    if ($deleteCashflow) {
                        $pdo->prepare('DELETE FROM transactions WHERE description LIKE :description')
                            ->execute([':description' => 'Baixa conta a pagar #' . $id . ':%']);
                    }
                }
            }

            if ($action === 'settle') {
                $id = (int) $_POST['id'];
                $payable = $pdo->prepare('SELECT * FROM accounts_payable WHERE id=:id');
                $payable->execute([':id' => $id]);
                $item = $payable->fetch();
                if (!$item) {
                    break;
                }

                $discount = moneyInput($_POST['discount'] ?? 0);
                $addition = moneyInput($_POST['addition'] ?? 0);
                $lateInterest = moneyInput($_POST['late_interest'] ?? 0);
                $paidAmount = round((float) $item['amount'] - $discount + $addition + $lateInterest, 2);
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

                $description = 'Baixa conta a pagar #' . $id . ': ' . $item['supplier'];
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

        case 'recebimento_clientes':
            $totalAmount = moneyInput($_POST['total_amount'] ?? 0);
            $discount = moneyInput($_POST['discount'] ?? 0);
            $interest = moneyInput($_POST['interest'] ?? 0);
            $netAmount = round($totalAmount - $discount + $interest, 2);

            $pdo->prepare('INSERT INTO customer_receipts (receipt_date, customer_name, total_amount, discount, interest, net_amount, payment_method)
                VALUES (:receipt_date, :customer_name, :total_amount, :discount, :interest, :net_amount, :payment_method)')
                ->execute([
                    ':receipt_date' => $_POST['receipt_date'],
                    ':customer_name' => trim((string) $_POST['customer_name']),
                    ':total_amount' => $totalAmount,
                    ':discount' => $discount,
                    ':interest' => $interest,
                    ':net_amount' => $netAmount,
                    ':payment_method' => trim((string) $_POST['payment_method']),
                ]);

            $pdo->prepare('INSERT INTO transactions (movement_type, amount, category, subcategory, origin_account, destination_account, description, occurred_on)
                VALUES (\'entrada\', :amount, \'recebimento_clientes\', :subcategory, :origin_account, :destination_account, :description, :occurred_on)')
                ->execute([
                    ':amount' => $netAmount,
                    ':subcategory' => trim((string) $_POST['payment_method']),
                    ':origin_account' => trim((string) $_POST['customer_name']),
                    ':destination_account' => 'caixa',
                    ':description' => 'Recebimento cliente: ' . trim((string) $_POST['customer_name']),
                    ':occurred_on' => $_POST['receipt_date'],
                ]);
            break;

        case 'vendas_prazo':
            $totalAmount = moneyInput($_POST['total_amount'] ?? 0);
            $returnOnCredit = moneyInput($_POST['return_on_credit'] ?? 0);
            $returnExchangeCredit = moneyInput($_POST['return_exchange_credit'] ?? 0);
            $netAmount = round($totalAmount - $returnOnCredit - $returnExchangeCredit, 2);

            $pdo->prepare('INSERT INTO credit_sales_totals (sale_date, sale_location, total_amount, return_on_credit, return_exchange_credit, net_amount)
                VALUES (:sale_date, :sale_location, :total_amount, :return_on_credit, :return_exchange_credit, :net_amount)')
                ->execute([
                    ':sale_date' => $_POST['sale_date'],
                    ':sale_location' => trim((string) ($_POST['sale_location'] ?? '')),
                    ':total_amount' => $totalAmount,
                    ':return_on_credit' => $returnOnCredit,
                    ':return_exchange_credit' => $returnExchangeCredit,
                    ':net_amount' => $netAmount,
                ]);
            break;

        case 'clientes_atraso':
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                $pdo->prepare('INSERT INTO overdue_customers (collection_entry_date, customer_name, amount, status)
                    VALUES (:collection_entry_date, :customer_name, :amount, :status)')
                    ->execute([
                        ':collection_entry_date' => $_POST['collection_entry_date'],
                        ':customer_name' => trim((string) $_POST['customer_name']),
                        ':amount' => moneyInput($_POST['amount'] ?? 0),
                        ':status' => in_array((string) $_POST['status'], ['vencido', 'spc', 'outra'], true) ? $_POST['status'] : 'vencido',
                    ]);
            }

            if ($action === 'edit') {
                $pdo->prepare('UPDATE overdue_customers
                    SET collection_entry_date=:collection_entry_date, customer_name=:customer_name, amount=:amount, status=:status
                    WHERE id=:id')
                    ->execute([
                        ':id' => (int) ($_POST['id'] ?? 0),
                        ':collection_entry_date' => $_POST['collection_entry_date'],
                        ':customer_name' => trim((string) $_POST['customer_name']),
                        ':amount' => moneyInput($_POST['amount'] ?? 0),
                        ':status' => in_array((string) $_POST['status'], ['vencido', 'spc', 'outra'], true) ? $_POST['status'] : 'vencido',
                    ]);
            }

            if ($action === 'settle') {
                $id = (int) ($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('SELECT * FROM overdue_customers WHERE id=:id');
                $stmt->execute([':id' => $id]);
                $item = $stmt->fetch();
                if ($item) {
                    $interest = moneyInput($_POST['interest'] ?? 0);
                    $discount = moneyInput($_POST['discount'] ?? 0);
                    $paidAmountInput = moneyInput($_POST['total_paid'] ?? 0);
                    $paymentDate = (string) ($_POST['payment_date'] ?? date('Y-m-d'));
                    $paymentMethod = trim((string) ($_POST['payment_method'] ?? ''));

                    $calculatedTotal = max(0, ((float) $item['amount']) - $discount + $interest);
                    $paidAmount = $paidAmountInput > 0 ? $paidAmountInput : $calculatedTotal;
                    $remainingAmount = round(max(0, (float) $item['amount'] - $paidAmount), 2);

                    if ($remainingAmount > 0) {
                        $pdo->prepare('UPDATE overdue_customers SET amount=:amount WHERE id=:id')
                            ->execute([':amount' => $remainingAmount, ':id' => $id]);
                    } else {
                        $pdo->prepare('DELETE FROM overdue_customers WHERE id=:id')
                            ->execute([':id' => $id]);
                    }

                    $pdo->prepare('INSERT INTO customer_receipts (receipt_date, customer_name, total_amount, discount, interest, net_amount, payment_method)
                        VALUES (:receipt_date, :customer_name, :total_amount, :discount, :interest, :net_amount, :payment_method)')
                        ->execute([
                            ':receipt_date' => $paymentDate,
                            ':customer_name' => (string) $item['customer_name'],
                            ':total_amount' => $paidAmount,
                            ':discount' => $discount,
                            ':interest' => $interest,
                            ':net_amount' => $paidAmount,
                            ':payment_method' => $paymentMethod,
                        ]);

                    $pdo->prepare('INSERT INTO transactions (movement_type, amount, category, subcategory, origin_account, destination_account, description, occurred_on)
                        VALUES (\'entrada\', :amount, \'recebimento_clientes\', :subcategory, :origin_account, :destination_account, :description, :occurred_on)')
                        ->execute([
                            ':amount' => $paidAmount,
                            ':subcategory' => $paymentMethod,
                            ':origin_account' => (string) $item['customer_name'],
                            ':destination_account' => 'caixa',
                            ':description' => 'Baixa cliente em atraso: ' . $item['customer_name'],
                            ':occurred_on' => $paymentDate,
                        ]);

                    $isCardPayment = str_contains(mb_strtolower($paymentMethod, 'UTF-8'), 'cart');
                    if ($isCardPayment) {
                        $cardFeePercent = moneyInput($_POST['card_fee_percent'] ?? 0);
                        $cardGross = $calculatedTotal;
                        $cardNet = round(max(0, $cardGross - ($cardGross * $cardFeePercent / 100)), 2);
                        $cardSaleDate = (string) ($_POST['card_sale_date'] ?? $paymentDate);
                        $cardReleaseDate = (string) ($_POST['card_expected_release_date'] ?? $paymentDate);
                        $cardType = normalizeCardType((string) ($_POST['card_type'] ?? 'credito_avista'));

                        $pdo->prepare('INSERT INTO card_receivables (machine, brand, card_type, fee_percent, gross_value, net_value, sale_date, expected_release_date, received, sale_location)
                            VALUES (:machine, :brand, :card_type, :fee_percent, :gross_value, :net_value, :sale_date, :expected_release_date, :received, :sale_location)')
                            ->execute([
                                ':machine' => trim((string) ($_POST['card_machine'] ?? 'Não informado')),
                                ':brand' => trim((string) ($_POST['card_brand'] ?? 'Não informado')),
                                ':card_type' => $cardType,
                                ':fee_percent' => $cardFeePercent,
                                ':gross_value' => $cardGross,
                                ':net_value' => $cardNet,
                                ':sale_date' => $cardSaleDate,
                                ':expected_release_date' => $cardReleaseDate,
                                ':received' => isset($_POST['card_received']) ? 1 : 0,
                                ':sale_location' => trim((string) ($_POST['card_sale_location'] ?? '')),
                            ]);
                    }
                }
            }
            break;

        case 'vendas_frente_caixa':
            $action = (string) ($_POST['action'] ?? 'create');
            if (in_array($action, ['create', 'edit'], true)) {
                $saleDate = (string) ($_POST['sale_date'] ?? date('Y-m-d'));
                $cashRegister = trim((string) ($_POST['cash_register'] ?? ''));
                $saleLocation = trim((string) ($_POST['sale_location'] ?? ''));
                $paymentMethod = trim((string) ($_POST['payment_method'] ?? ''));
                $amount = moneyInput($_POST['amount'] ?? 0);
                $allowedLocations = ['Caixa Loja', 'Caixa Parafuso'];
                if (!in_array($saleLocation, $allowedLocations, true)) {
                    $saleLocation = 'Caixa Loja';
                }
                $allowedPaymentMethods = [
                    'Dinheiro',
                    'Cheque',
                    'Cartão Débito',
                    'Cartão Crédito',
                    'Cartão Parcelado',
                    'Pix e TED',
                    'Pix QRCode',
                ];
                if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
                    $paymentMethod = 'Dinheiro';
                }
                if ($action === 'create') {
                    $pdo->prepare('INSERT INTO transactions (movement_type, amount, category, subcategory, origin_account, destination_account, description, occurred_on)
                        VALUES (\'entrada\', :amount, \'vendas_frente_caixa\', :subcategory, :origin_account, :destination_account, :description, :occurred_on)')
                        ->execute([
                            ':amount' => $amount,
                            ':subcategory' => $paymentMethod,
                            ':origin_account' => $cashRegister,
                            ':destination_account' => $saleLocation,
                            ':description' => 'Venda frente de caixa',
                            ':occurred_on' => $saleDate,
                        ]);
                    $transactionId = (int) $pdo->lastInsertId();

                    $pdo->prepare('INSERT INTO front_cash_sales (sale_date, cash_register, sale_location, payment_method, amount, transaction_id)
                        VALUES (:sale_date, :cash_register, :sale_location, :payment_method, :amount, :transaction_id)')
                        ->execute([
                            ':sale_date' => $saleDate,
                            ':cash_register' => $cashRegister,
                            ':sale_location' => $saleLocation,
                            ':payment_method' => $paymentMethod,
                            ':amount' => $amount,
                            ':transaction_id' => $transactionId,
                        ]);
                } else {
                    $id = (int) ($_POST['id'] ?? 0);
                    $rowStmt = $pdo->prepare('SELECT transaction_id FROM front_cash_sales WHERE id=:id');
                    $rowStmt->execute([':id' => $id]);
                    $transactionId = (int) ($rowStmt->fetchColumn() ?: 0);
                    if ($transactionId > 0) {
                        $pdo->prepare('UPDATE transactions
                            SET amount=:amount, subcategory=:subcategory, origin_account=:origin_account, destination_account=:destination_account, occurred_on=:occurred_on
                            WHERE id=:id')
                            ->execute([
                                ':id' => $transactionId,
                                ':amount' => $amount,
                                ':subcategory' => $paymentMethod,
                                ':origin_account' => $cashRegister,
                                ':destination_account' => $saleLocation,
                                ':occurred_on' => $saleDate,
                            ]);
                    }
                    $pdo->prepare('UPDATE front_cash_sales
                        SET sale_date=:sale_date, cash_register=:cash_register, sale_location=:sale_location, payment_method=:payment_method, amount=:amount
                        WHERE id=:id')
                        ->execute([
                            ':id' => $id,
                            ':sale_date' => $saleDate,
                            ':cash_register' => $cashRegister,
                            ':sale_location' => $saleLocation,
                            ':payment_method' => $paymentMethod,
                            ':amount' => $amount,
                        ]);
                }
            }
            if ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                $rowStmt = $pdo->prepare('SELECT transaction_id FROM front_cash_sales WHERE id=:id');
                $rowStmt->execute([':id' => $id]);
                $transactionId = (int) ($rowStmt->fetchColumn() ?: 0);
                $pdo->prepare('DELETE FROM front_cash_sales WHERE id=:id')->execute([':id' => $id]);
                if ($transactionId > 0) {
                    $pdo->prepare('DELETE FROM transactions WHERE id=:id')->execute([':id' => $transactionId]);
                }
            }
            if (in_array($action, ['gas_create', 'gas_edit'], true)) {
                $saleDate = (string) ($_POST['gas_sale_date'] ?? date('Y-m-d'));
                $seller = trim((string) ($_POST['seller'] ?? ''));
                $qtyRefill = max(0, (int) ($_POST['qty_refill'] ?? 0));
                $qtyFull = max(0, (int) ($_POST['qty_full'] ?? 0));
                $deliveryType = 'retirada';
                $paymentMethod = 'Não informado';
                if ($action === 'gas_create') {
                    $pdo->prepare('INSERT INTO front_cash_gas_sales (sale_date, seller, qty_refill, qty_full, delivery_type, payment_method)
                        VALUES (:sale_date, :seller, :qty_refill, :qty_full, :delivery_type, :payment_method)')
                        ->execute([
                            ':sale_date' => $saleDate,
                            ':seller' => $seller,
                            ':qty_refill' => $qtyRefill,
                            ':qty_full' => $qtyFull,
                            ':delivery_type' => $deliveryType,
                            ':payment_method' => $paymentMethod,
                        ]);
                } else {
                    $pdo->prepare('UPDATE front_cash_gas_sales
                        SET sale_date=:sale_date, seller=:seller, qty_refill=:qty_refill, qty_full=:qty_full, delivery_type=:delivery_type, payment_method=:payment_method
                        WHERE id=:id')
                        ->execute([
                            ':id' => (int) ($_POST['id'] ?? 0),
                            ':sale_date' => $saleDate,
                            ':seller' => $seller,
                            ':qty_refill' => $qtyRefill,
                            ':qty_full' => $qtyFull,
                            ':delivery_type' => $deliveryType,
                            ':payment_method' => $paymentMethod,
                        ]);
                }
            }
            if ($action === 'gas_delete') {
                $pdo->prepare('DELETE FROM front_cash_gas_sales WHERE id=:id')
                    ->execute([':id' => (int) ($_POST['id'] ?? 0)]);
            }
            break;

        case 'saida_financeiro':
            $expenseDate = (string) ($_POST['expense_date'] ?? date('Y-m-d'));
            $name = trim((string) ($_POST['name'] ?? ''));
            $amount = moneyInput($_POST['amount'] ?? 0);
            $paymentMethod = trim((string) ($_POST['payment_method'] ?? ''));

            $pdo->prepare('INSERT INTO finance_expenses (expense_date, name, amount, payment_method)
                VALUES (:expense_date, :name, :amount, :payment_method)')
                ->execute([
                    ':expense_date' => $expenseDate,
                    ':name' => $name,
                    ':amount' => $amount,
                    ':payment_method' => $paymentMethod,
                ]);

            $pdo->prepare('INSERT INTO transactions (movement_type, amount, category, subcategory, origin_account, destination_account, description, occurred_on)
                VALUES (\'saida\', :amount, \'saida_financeiro\', :subcategory, \'caixa\', \'fornecedor\', :description, :occurred_on)')
                ->execute([
                    ':amount' => $amount,
                    ':subcategory' => $paymentMethod,
                    ':description' => 'Saída: ' . $name,
                    ':occurred_on' => $expenseDate,
                ]);
            break;

        case 'funcionarios':
            $action = $_POST['action'] ?? '';
            if ($action === 'employee_add') {
                $fullName = trim((string) ($_POST['full_name'] ?? $_POST['name'] ?? ''));
                $role = trim((string) ($_POST['function_role'] ?? $_POST['role'] ?? 'Sem função'));
                $profileData = $_POST;
                unset($profileData['action']);
                $pdo->prepare('INSERT INTO employees (name, role, profile_data) VALUES (:name, :role, :profile_data)')
                    ->execute([
                        ':name' => $fullName,
                        ':role' => $role,
                        ':profile_data' => json_encode($profileData, JSON_UNESCAPED_UNICODE),
                    ]);
            }
            if ($action === 'employee_update') {
                $pdo->prepare('UPDATE employees SET name=:name, role=:role WHERE id=:id')
                    ->execute([
                        ':id' => (int) ($_POST['id'] ?? 0),
                        ':name' => trim((string) ($_POST['name'] ?? '')),
                        ':role' => trim((string) ($_POST['role'] ?? '')),
                    ]);
            }
            if ($action === 'employee_delete') {
                $pdo->prepare('DELETE FROM employees WHERE id=:id')
                    ->execute([':id' => (int) ($_POST['id'] ?? 0)]);
            }
            if ($action === 'debt_add') {
                $pdo->prepare('INSERT INTO employee_debts (employee_id, debt_date, description, amount, status)
                    VALUES (:employee_id, :debt_date, :description, :amount, :status)')
                    ->execute([
                        ':employee_id' => (int) $_POST['employee_id'],
                        ':debt_date' => $_POST['debt_date'],
                        ':description' => trim((string) ($_POST['description'] ?? '')),
                        ':amount' => moneyInput($_POST['amount'] ?? 0),
                        ':status' => in_array((string) $_POST['status'], ['aberto', 'quitado'], true) ? $_POST['status'] : 'aberto',
                    ]);
            }
            break;

        case 'veiculos':
            $action = $_POST['action'] ?? '';
            if ($action === 'vehicle_add') {
                $pdo->prepare('INSERT INTO vehicles (name, plate, model, year) VALUES (:name, :plate, :model, :year)')
                    ->execute([
                        ':name' => trim((string) $_POST['name']),
                        ':plate' => trim((string) ($_POST['plate'] ?? '')),
                        ':model' => trim((string) ($_POST['model'] ?? '')),
                        ':year' => trim((string) ($_POST['year'] ?? '')),
                    ]);
            }
            if ($action === 'vehicle_expense_add') {
                $pdo->prepare('INSERT INTO vehicle_expenses (vehicle_id, expense_date, expense_type, description, amount)
                    VALUES (:vehicle_id, :expense_date, :expense_type, :description, :amount)')
                    ->execute([
                        ':vehicle_id' => (int) $_POST['vehicle_id'],
                        ':expense_date' => $_POST['expense_date'],
                        ':expense_type' => in_array((string) $_POST['expense_type'], ['despesa', 'manutencao', 'abastecimento'], true) ? $_POST['expense_type'] : 'despesa',
                        ':description' => trim((string) ($_POST['description'] ?? '')),
                        ':amount' => moneyInput($_POST['amount'] ?? 0),
                    ]);
            }
            break;

        case 'cartoes':
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                $gross = (float) $_POST['gross_value'];
                $fee = (float) $_POST['fee_percent'];
                $net = $gross - ($gross * $fee / 100);
                $cardType = normalizeCardType((string) $_POST['card_type']);
                $stmt = $pdo->prepare('INSERT INTO card_receivables (machine, brand, card_type, fee_percent, gross_value, net_value, sale_date, expected_release_date, received)
                    VALUES (:machine,:brand,:card_type,:fee_percent,:gross_value,:net_value,:sale_date,:expected_release_date,:received)');
                $stmt->execute([
                    ':machine' => trim($_POST['machine']),
                    ':brand' => trim($_POST['brand']),
                    ':card_type' => $cardType,
                    ':fee_percent' => $fee,
                    ':gross_value' => $gross,
                    ':net_value' => $net,
                    ':sale_date' => $_POST['sale_date'],
                    ':expected_release_date' => $_POST['expected_release_date'],
                    ':received' => isset($_POST['received']) ? 1 : 0,
                ]);
                $pdo->prepare('UPDATE card_receivables SET sale_location=:sale_location WHERE id=last_insert_rowid()')
                    ->execute([':sale_location' => trim((string) ($_POST['sale_location'] ?? ''))]);
            }

            if ($action === 'edit') {
                $gross = (float) $_POST['gross_value'];
                $fee = (float) $_POST['fee_percent'];
                $net = $gross - ($gross * $fee / 100);
                $cardType = normalizeCardType((string) $_POST['card_type']);
                $stmt = $pdo->prepare('UPDATE card_receivables
                    SET machine=:machine, brand=:brand, card_type=:card_type, fee_percent=:fee_percent, gross_value=:gross_value, net_value=:net_value, sale_date=:sale_date, expected_release_date=:expected_release_date, received=:received, sale_location=:sale_location
                    WHERE id=:id');
                $stmt->execute([
                    ':id' => (int) $_POST['id'],
                    ':machine' => trim($_POST['machine']),
                    ':brand' => trim($_POST['brand']),
                    ':card_type' => $cardType,
                    ':fee_percent' => $fee,
                    ':gross_value' => $gross,
                    ':net_value' => $net,
                    ':sale_date' => $_POST['sale_date'],
                    ':expected_release_date' => $_POST['expected_release_date'],
                    ':received' => isset($_POST['received']) ? 1 : 0,
                    ':sale_location' => trim((string) ($_POST['sale_location'] ?? '')),
                ]);
            }

            if ($action === 'delete') {
                $pdo->prepare('DELETE FROM card_receivables WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id']]);
            }

            if ($action === 'settle') {
                $id = (int) $_POST['id'];
                $cardStmt = $pdo->prepare('SELECT * FROM card_receivables WHERE id=:id');
                $cardStmt->execute([':id' => $id]);
                $card = $cardStmt->fetch();
                if ($card) {
                    $anticipationFeePercent = max(0, (float) ($_POST['anticipation_fee_percent'] ?? 0));
                    $discount = ((float) $card['net_value'] * $anticipationFeePercent) / 100;
                    $isCanceled = isset($_POST['canceled']) ? 1 : 0;
                    $receivedAmount = max(0, (float) $card['net_value'] - $discount);
                    $settleSubcategory = trim((string) (($_POST['settle_subcategory'] ?? '') ?: (string) $card['card_type']));
                    $settleCategory = trim((string) ($_POST['settle_category'] ?? ''));

                    $pdo->prepare('UPDATE card_receivables SET received=:received, canceled=:canceled, anticipation_discount=:anticipation_discount WHERE id=:id')
                        ->execute([
                            ':id' => $id,
                            ':received' => $isCanceled ? 0 : 1,
                            ':canceled' => $isCanceled,
                            ':anticipation_discount' => $discount,
                        ]);

                    if (!$isCanceled) {
                        $pdo->prepare('INSERT INTO transactions (movement_type, amount, category, subcategory, origin_account, destination_account, description, occurred_on)
                            VALUES (\'entrada\', :amount, \'cartoes_recebidos\', :subcategory, :origin_account, :destination_account, :description, :occurred_on)')
                            ->execute([
                                ':amount' => $receivedAmount,
                                ':subcategory' => $settleSubcategory,
                                ':origin_account' => (string) $card['machine'],
                                ':destination_account' => trim((string) ($_POST['destination_account'] ?? 'caixa')),
                                ':description' => 'Baixa de cartão ' . $card['brand'],
                                ':occurred_on' => $_POST['received_on'] ?: date('Y-m-d'),
                            ]);
                        if ($settleCategory !== '') {
                            $pdo->prepare("UPDATE transactions SET category=:category WHERE id=last_insert_rowid()")
                                ->execute([':category' => $settleCategory]);
                        }
                    }
                }
            }
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

            if ($action === 'card_machine_add') {
                $pdo->prepare('INSERT INTO card_machines (name) VALUES (:name)')
                    ->execute([':name' => trim($_POST['name'])]);
            }
            if ($action === 'card_machine_update') {
                $pdo->prepare('UPDATE card_machines SET name=:name WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id'], ':name' => trim($_POST['name'])]);
            }
            if ($action === 'card_machine_delete') {
                $pdo->prepare('DELETE FROM card_machines WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id']]);
            }

            if ($action === 'card_brand_add') {
                $pdo->prepare('INSERT INTO card_brands (name) VALUES (:name)')
                    ->execute([':name' => trim($_POST['name'])]);
            }
            if ($action === 'card_brand_update') {
                $pdo->prepare('UPDATE card_brands SET name=:name WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id'], ':name' => trim($_POST['name'])]);
            }
            if ($action === 'card_brand_delete') {
                $pdo->prepare('DELETE FROM card_brands WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id']]);
            }

            if ($action === 'card_payment_config_add') {
                $defaultDays = str_contains(mb_strtolower((string) $_POST['name'], 'UTF-8'), 'debito') ? 1 : 30;
                $name = trim((string) $_POST['name']);
                $exists = (int) sumValue($pdo, 'SELECT COUNT(*) FROM card_payment_configs WHERE LOWER(name)=LOWER(:name)', [':name' => $name]);
                if ($exists === 0) {
                    $pdo->prepare('INSERT INTO card_payment_configs (name, fee_percent, release_days) VALUES (:name,0,:days)')
                        ->execute([
                            ':name' => $name,
                            ':days' => $defaultDays,
                        ]);
                }
            }
            if ($action === 'card_payment_config_update') {
                $defaultDays = str_contains(mb_strtolower((string) $_POST['name'], 'UTF-8'), 'debito') ? 1 : 30;
                $id = (int) $_POST['id'];
                $name = trim((string) $_POST['name']);
                $exists = (int) sumValue($pdo, 'SELECT COUNT(*) FROM card_payment_configs WHERE LOWER(name)=LOWER(:name) AND id<>:id', [':name' => $name, ':id' => $id]);
                if ($exists === 0) {
                    $pdo->prepare('UPDATE card_payment_configs SET name=:name, release_days=:days WHERE id=:id')
                        ->execute([
                            ':id' => $id,
                            ':name' => $name,
                            ':days' => $defaultDays,
                        ]);
                }
            }
            if ($action === 'card_payment_config_delete') {
                $pdo->prepare('DELETE FROM card_payment_configs WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id']]);
            }

            if ($action === 'card_rate_rule_add') {
                $pdo->prepare('INSERT INTO card_rate_rules (machine_id, brand_id, payment_config_id, fee_percent, release_days)
                    VALUES (:machine_id,:brand_id,:payment_config_id,:fee_percent,:release_days)')
                    ->execute([
                        ':machine_id' => (int) $_POST['machine_id'],
                        ':brand_id' => (int) $_POST['brand_id'],
                        ':payment_config_id' => (int) $_POST['payment_config_id'],
                        ':fee_percent' => (float) $_POST['fee_percent'],
                        ':release_days' => (int) $_POST['release_days'],
                    ]);
            }
            if ($action === 'card_rate_rule_update') {
                $pdo->prepare('UPDATE card_rate_rules SET machine_id=:machine_id, brand_id=:brand_id, payment_config_id=:payment_config_id, fee_percent=:fee_percent, release_days=:release_days WHERE id=:id')
                    ->execute([
                        ':id' => (int) $_POST['id'],
                        ':machine_id' => (int) $_POST['machine_id'],
                        ':brand_id' => (int) $_POST['brand_id'],
                        ':payment_config_id' => (int) $_POST['payment_config_id'],
                        ':fee_percent' => (float) $_POST['fee_percent'],
                        ':release_days' => (int) $_POST['release_days'],
                    ]);
            }
            if ($action === 'card_rate_rule_delete') {
                $pdo->prepare('DELETE FROM card_rate_rules WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id']]);
            }

            if ($action === 'sale_location_add') {
                $pdo->prepare('INSERT INTO sale_locations (name) VALUES (:name)')
                    ->execute([':name' => trim($_POST['name'])]);
            }
            if ($action === 'sale_location_update') {
                $pdo->prepare('UPDATE sale_locations SET name=:name WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id'], ':name' => trim($_POST['name'])]);
            }
            if ($action === 'sale_location_delete') {
                $pdo->prepare('DELETE FROM sale_locations WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id']]);
            }

            if ($action === 'front_cash_register_add') {
                $pdo->prepare('INSERT INTO front_cash_registers (name) VALUES (:name)')
                    ->execute([':name' => trim((string) $_POST['name'])]);
            }
            if ($action === 'front_cash_register_update') {
                $pdo->prepare('UPDATE front_cash_registers SET name=:name WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id'], ':name' => trim((string) $_POST['name'])]);
            }
            if ($action === 'front_cash_register_delete') {
                $pdo->prepare('DELETE FROM front_cash_registers WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id']]);
            }
            if ($action === 'front_cash_vendor_add') {
                $pdo->prepare('INSERT INTO front_cash_vendors (name) VALUES (:name)')
                    ->execute([':name' => trim((string) $_POST['name'])]);
            }
            if ($action === 'front_cash_vendor_update') {
                $pdo->prepare('UPDATE front_cash_vendors SET name=:name WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id'], ':name' => trim((string) $_POST['name'])]);
            }
            if ($action === 'front_cash_vendor_delete') {
                $pdo->prepare('DELETE FROM front_cash_vendors WHERE id=:id')
                    ->execute([':id' => (int) $_POST['id']]);
            }

            if ($action === 'system_reset') {
                $targets = array_map('strval', $_POST['reset_targets'] ?? []);
                $resetMap = [
                    'transactions' => [
                        'DELETE FROM transactions',
                        'UPDATE bank_accounts SET current_balance=initial_balance',
                    ],
                    'payables' => ['DELETE FROM accounts_payable'],
                    'cards' => ['DELETE FROM card_receivables'],
                    'checks' => ['DELETE FROM checks_control'],
                    'reconciliation' => ['DELETE FROM bank_reconciliation'],
                    'closing' => ['DELETE FROM cash_closing'],
                    'suppliers' => ['DELETE FROM suppliers'],
                    'settings' => [
                        'DELETE FROM card_rate_rules',
                        'DELETE FROM front_cash_registers',
                        'DELETE FROM front_cash_vendors',
                        'DELETE FROM front_cash_sales',
                        'DELETE FROM front_cash_gas_sales',
                        'DELETE FROM sale_locations',
                        'DELETE FROM card_payment_configs',
                        'DELETE FROM card_brands',
                        'DELETE FROM card_machines',
                        'DELETE FROM payable_types',
                        'DELETE FROM companies',
                        'DELETE FROM payment_methods',
                        'DELETE FROM cashflow_categories',
                        'DELETE FROM bank_accounts',
                    ],
                ];

                $pdo->beginTransaction();
                try {
                    foreach ($targets as $target) {
                        foreach ($resetMap[$target] ?? [] as $sql) {
                            $pdo->exec($sql);
                        }
                    }
                    $pdo->commit();
                } catch (Throwable $exception) {
                    $pdo->rollBack();
                }
            }
            break;
    }
}

function money(float $v): string
{
    return 'R$ ' . number_format($v, 2, ',', '.');
}

function dateBr(?string $date): string
{
    $value = trim((string) $date);
    if ($value === '') {
        return '';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }

    return date('d/m/Y', $ts);
}

function moneyInput(mixed $value): float
{
    $normalized = trim((string) $value);
    if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);
    } elseif (str_contains($normalized, ',')) {
        $normalized = str_replace(',', '.', $normalized);
    }
    if ($normalized === '' || !is_numeric($normalized)) {
        return 0.0;
    }

    return (float) $normalized;
}

function normalizeCardType(string $value): string
{
    $normalized = mb_strtolower(trim($value), 'UTF-8');
    if (str_contains($normalized, 'debito')) {
        return 'debito';
    }
    if (str_contains($normalized, 'parcel')) {
        return 'credito_parcelado';
    }

    return 'credito_avista';
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
$cashflowByCategory = fetchAll($pdo, "SELECT
    COALESCE(NULLIF(category, ''), 'Sem categoria') AS category,
    SUM(CASE WHEN movement_type='entrada' THEN amount ELSE 0 END) AS entradas,
    SUM(CASE WHEN movement_type='saida' THEN amount ELSE 0 END) AS saidas,
    SUM(CASE WHEN movement_type='entrada' THEN amount ELSE -amount END) AS saldo
    FROM transactions
    WHERE occurred_on BETWEEN :s AND :e
    GROUP BY category
    ORDER BY saldo DESC", [':s' => $filterStart, ':e' => $filterEnd]);

$cashflowBySubcategory = fetchAll($pdo, "SELECT
    COALESCE(NULLIF(category, ''), 'Sem categoria') AS category,
    COALESCE(NULLIF(subcategory, ''), 'Sem subcategoria') AS subcategory,
    SUM(CASE WHEN movement_type='entrada' THEN amount ELSE 0 END) AS entradas,
    SUM(CASE WHEN movement_type='saida' THEN amount ELSE 0 END) AS saidas,
    SUM(CASE WHEN movement_type='entrada' THEN amount ELSE -amount END) AS saldo
    FROM transactions
    WHERE occurred_on BETWEEN :s AND :e
    GROUP BY category, subcategory
    ORDER BY category, saldo DESC", [':s' => $filterStart, ':e' => $filterEnd]);

$payableDueDateFrom = trim((string) ($_GET['payable_due_date_from'] ?? ''));
$payableDueDateTo = trim((string) ($_GET['payable_due_date_to'] ?? ''));
$payableCreatedDateFrom = trim((string) ($_GET['payable_created_date_from'] ?? ''));
$payableCreatedDateTo = trim((string) ($_GET['payable_created_date_to'] ?? ''));
$payableSupplierFilter = trim((string) ($_GET['payable_supplier'] ?? ''));
$payableStatusFilter = trim((string) ($_GET['payable_status'] ?? ''));
$payableCompanyFilter = trim((string) ($_GET['payable_company'] ?? ''));
$payableTypeFilter = trim((string) ($_GET['payable_type_filter'] ?? ''));

$payablesSql = 'SELECT *, CASE WHEN status = "aberto" AND due_date < :today THEN "atrasado" ELSE status END AS display_status FROM accounts_payable';
$payablesParams = [':today' => $today];
$payablesConditions = [];

if ($payableDueDateFrom !== '') {
    $payablesConditions[] = 'due_date >= :due_from';
    $payablesParams[':due_from'] = $payableDueDateFrom;
}
if ($payableDueDateTo !== '') {
    $payablesConditions[] = 'due_date <= :due_to';
    $payablesParams[':due_to'] = $payableDueDateTo;
}
if ($payableCreatedDateFrom !== '') {
    $payablesConditions[] = 'date(created_at) >= :created_from';
    $payablesParams[':created_from'] = $payableCreatedDateFrom;
}
if ($payableCreatedDateTo !== '') {
    $payablesConditions[] = 'date(created_at) <= :created_to';
    $payablesParams[':created_to'] = $payableCreatedDateTo;
}
if ($payableSupplierFilter !== '') {
    $payablesConditions[] = 'supplier LIKE :supplier';
    $payablesParams[':supplier'] = '%' . $payableSupplierFilter . '%';
}
if ($payableStatusFilter !== '') {
    $payablesConditions[] = 'status = :status';
    $payablesParams[':status'] = $payableStatusFilter;
}
if ($payableCompanyFilter !== '') {
    $payablesConditions[] = 'company LIKE :company';
    $payablesParams[':company'] = '%' . $payableCompanyFilter . '%';
}
if ($payableTypeFilter !== '') {
    $payablesConditions[] = 'payable_type LIKE :payable_type';
    $payablesParams[':payable_type'] = '%' . $payableTypeFilter . '%';
}

if ($payablesConditions !== []) {
    $payablesSql .= ' WHERE ' . implode(' AND ', $payablesConditions);
}
$payablesSql .= ' ORDER BY due_date ASC, id DESC';
$payables = fetchAll($pdo, $payablesSql, $payablesParams);
$suppliers = fetchAll($pdo, 'SELECT * FROM suppliers ORDER BY name');
$paymentMethods = fetchAll($pdo, 'SELECT * FROM payment_methods ORDER BY name');
$companies = fetchAll($pdo, 'SELECT * FROM companies ORDER BY name');
$payableTypes = fetchAll($pdo, 'SELECT * FROM payable_types ORDER BY name');
$cardMachines = fetchAll($pdo, 'SELECT * FROM card_machines ORDER BY name');
$cardBrands = fetchAll($pdo, 'SELECT * FROM card_brands ORDER BY name');
$cardPaymentConfigs = fetchAll($pdo, 'SELECT * FROM card_payment_configs ORDER BY name');
$saleLocations = fetchAll($pdo, 'SELECT * FROM sale_locations ORDER BY name');
$frontCashRegisters = fetchAll($pdo, 'SELECT * FROM front_cash_registers ORDER BY name');
$frontCashVendors = fetchAll($pdo, 'SELECT * FROM front_cash_vendors ORDER BY name');
$frontCashFilters = [
    'date_from' => trim((string) ($_GET['front_cash_date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['front_cash_date_to'] ?? '')),
    'cash_register' => trim((string) ($_GET['front_cash_register'] ?? '')),
    'sale_location' => trim((string) ($_GET['front_cash_location'] ?? '')),
    'payment_method' => trim((string) ($_GET['front_cash_payment_method'] ?? '')),
];
$frontCashSql = 'SELECT * FROM front_cash_sales';
$frontCashParams = [];
$frontCashWhere = [];
if ($frontCashFilters['date_from'] !== '') {
    $frontCashWhere[] = 'sale_date >= :front_cash_date_from';
    $frontCashParams[':front_cash_date_from'] = $frontCashFilters['date_from'];
}
if ($frontCashFilters['date_to'] !== '') {
    $frontCashWhere[] = 'sale_date <= :front_cash_date_to';
    $frontCashParams[':front_cash_date_to'] = $frontCashFilters['date_to'];
}
if ($frontCashFilters['cash_register'] !== '') {
    $frontCashWhere[] = 'cash_register = :front_cash_register';
    $frontCashParams[':front_cash_register'] = $frontCashFilters['cash_register'];
}
if ($frontCashFilters['sale_location'] !== '') {
    $frontCashWhere[] = 'sale_location = :front_cash_location';
    $frontCashParams[':front_cash_location'] = $frontCashFilters['sale_location'];
}
if ($frontCashFilters['payment_method'] !== '') {
    $frontCashWhere[] = 'payment_method = :front_cash_payment_method';
    $frontCashParams[':front_cash_payment_method'] = $frontCashFilters['payment_method'];
}
if ($frontCashWhere !== []) {
    $frontCashSql .= ' WHERE ' . implode(' AND ', $frontCashWhere);
}
$frontCashSql .= ' ORDER BY sale_date DESC, id DESC';
$frontCashSales = fetchAll($pdo, $frontCashSql, $frontCashParams);
$frontCashGasSales = fetchAll($pdo, 'SELECT * FROM front_cash_gas_sales ORDER BY sale_date DESC, id DESC');
$cardRateRules = fetchAll($pdo, 'SELECT r.*, m.name AS machine_name, b.name AS brand_name, p.name AS payment_name FROM card_rate_rules r
    JOIN card_machines m ON m.id=r.machine_id
    JOIN card_brands b ON b.id=r.brand_id
    JOIN card_payment_configs p ON p.id=r.payment_config_id
    ORDER BY m.name, b.name, p.name');
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
$customerReceipts = fetchAll($pdo, 'SELECT * FROM customer_receipts ORDER BY receipt_date DESC, id DESC');
$creditSalesTotals = fetchAll($pdo, 'SELECT * FROM credit_sales_totals ORDER BY sale_date DESC, id DESC');
$financeExpenses = fetchAll($pdo, 'SELECT * FROM finance_expenses ORDER BY expense_date DESC, id DESC');
$employees = fetchAll($pdo, 'SELECT * FROM employees ORDER BY role, name');
$employeeDebts = fetchAll($pdo, 'SELECT d.*, e.name AS employee_name, e.role AS employee_role FROM employee_debts d JOIN employees e ON e.id=d.employee_id ORDER BY d.debt_date DESC, d.id DESC');
$vehicles = fetchAll($pdo, 'SELECT * FROM vehicles ORDER BY name');
$vehicleExpenses = fetchAll($pdo, 'SELECT ve.*, v.name AS vehicle_name, v.plate AS vehicle_plate FROM vehicle_expenses ve JOIN vehicles v ON v.id=ve.vehicle_id ORDER BY ve.expense_date DESC, ve.id DESC');
$overdueDateFrom = trim((string) ($_GET['overdue_date_from'] ?? ''));
$overdueDateTo = trim((string) ($_GET['overdue_date_to'] ?? ''));
$overdueStatusFilter = trim((string) ($_GET['overdue_status'] ?? ''));
$overdueNameFilter = trim((string) ($_GET['overdue_name'] ?? ''));
$overdueSort = trim((string) ($_GET['overdue_sort'] ?? 'date_desc'));
$overdueSql = 'SELECT * FROM overdue_customers';
$overdueParams = [];
$overdueConditions = [];
if ($overdueDateFrom !== '') {
    $overdueConditions[] = 'collection_entry_date >= :overdue_date_from';
    $overdueParams[':overdue_date_from'] = $overdueDateFrom;
}
if ($overdueDateTo !== '') {
    $overdueConditions[] = 'collection_entry_date <= :overdue_date_to';
    $overdueParams[':overdue_date_to'] = $overdueDateTo;
}
if ($overdueStatusFilter !== '') {
    $overdueConditions[] = 'status = :overdue_status';
    $overdueParams[':overdue_status'] = $overdueStatusFilter;
}
if ($overdueNameFilter !== '') {
    $overdueConditions[] = 'customer_name LIKE :overdue_name';
    $overdueParams[':overdue_name'] = '%' . $overdueNameFilter . '%';
}
if ($overdueConditions !== []) {
    $overdueSql .= ' WHERE ' . implode(' AND ', $overdueConditions);
}
$overdueSql .= $overdueSort === 'amount_desc' ? ' ORDER BY amount DESC, collection_entry_date DESC' : ' ORDER BY collection_entry_date DESC, id DESC';
$overdueCustomers = fetchAll($pdo, $overdueSql, $overdueParams);
$cards = fetchAll($pdo, 'SELECT * FROM card_receivables ORDER BY sale_date DESC');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');
$salesToday = fetchAll($pdo, 'SELECT * FROM card_receivables WHERE sale_date=:today ORDER BY id DESC', [':today' => $today]);
$salesDailyTotal = sumValue($pdo, 'SELECT COALESCE(SUM(gross_value),0) FROM card_receivables WHERE sale_date=:today AND canceled=0', [':today' => $today]);
$salesWeeklyTotal = sumValue($pdo, 'SELECT COALESCE(SUM(gross_value),0) FROM card_receivables WHERE sale_date BETWEEN :start AND :end AND canceled=0', [':start' => $weekStart, ':end' => $today]);
$salesMonthlyTotal = sumValue($pdo, 'SELECT COALESCE(SUM(gross_value),0) FROM card_receivables WHERE sale_date BETWEEN :start AND :end AND canceled=0', [':start' => $monthStart, ':end' => $today]);
$salesByLocationToday = fetchAll($pdo, 'SELECT COALESCE(NULLIF(sale_location, \'\'), \'Sem local\') AS sale_location, COUNT(*) AS total_sales, COALESCE(SUM(gross_value),0) AS gross_total, COALESCE(SUM(net_value),0) AS net_total
    FROM card_receivables
    WHERE sale_date=:today AND canceled=0
    GROUP BY sale_location
    ORDER BY gross_total DESC', [':today' => $today]);
$checks = fetchAll($pdo, 'SELECT * FROM checks_control ORDER BY due_date ASC');
$banks = fetchAll($pdo, 'SELECT * FROM bank_accounts ORDER BY name');
$reconciliations = fetchAll($pdo, 'SELECT br.*, ba.name bank_name FROM bank_reconciliation br JOIN bank_accounts ba ON ba.id=br.bank_account_id ORDER BY movement_date DESC');
$closings = fetchAll($pdo, 'SELECT * FROM cash_closing ORDER BY opening_date DESC');

function dreCategorySum(PDO $pdo, string $monthStart, array $labels): float
{
    $clauses = [];
    $params = [':m' => $monthStart];
    foreach ($labels as $idx => $label) {
        $key = ':label' . $idx;
        $clauses[] = "LOWER(TRIM(category)) = $key";
        $params[$key] = mb_strtolower(trim($label), 'UTF-8');
    }

    $sql = "SELECT COALESCE(SUM(amount),0) FROM transactions
        WHERE movement_type='saida' AND occurred_on>=:m AND (" . implode(' OR ', $clauses) . ')';

    return sumValue($pdo, $sql, $params);
}

$dre = [
    'receitas' => $monthEntries,
    'custos' => dreCategorySum($pdo, $monthStart, ['custos', 'custo']),
    'despesas_fixas' => dreCategorySum($pdo, $monthStart, ['despesas fixas', 'despesas_fixas']),
    'despesas_variaveis' => dreCategorySum($pdo, $monthStart, ['despesas variáveis', 'despesas variaveis', 'despesas_variaveis']),
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
        <a href="?module=vendas">Vendas</a>
        <div class="menu-group">
            <button type="button" class="menu-toggle" onclick="toggleMenu(event, 'financeiroMenu')">Financeiro ▾</button>
            <div id="financeiroMenu" class="menu-dropdown">
                <a href="?module=recebimento_clientes">Recebimento de Clientes</a>
                <a href="?module=saida_financeiro">Saída</a>
                <a href="?module=vendas_prazo">Vendas a Prazo</a>
                <a href="?module=clientes_atraso">Clientes em Atraso</a>
            </div>
        </div>
        <div class="menu-group">
            <button type="button" class="menu-toggle" onclick="toggleMenu(event, 'caixaMenu')">Caixa ▾</button>
            <div id="caixaMenu" class="menu-dropdown">
                <a href="?module=vendas_frente_caixa">Vendas Frente de Caixa</a>
            </div>
        </div>
        <div class="menu-group">
            <button type="button" class="menu-toggle" onclick="toggleMenu(event, 'gestaoFinanceiraMenu')">Gestão Financeira ▾</button>
            <div id="gestaoFinanceiraMenu" class="menu-dropdown">
                <a href="?module=pagar">Contas a Pagar</a>
                <a href="?module=fluxo">Fluxo de Caixa</a>
                <a href="?module=veiculos">Controle de Veículos</a>
                <a href="?module=cartoes">Cartões</a>
                <a href="?module=cheques">Cheques</a>
                <a href="?module=dre">DRE</a>
            </div>
        </div>
        <a href="?module=conciliacao">Conciliação Bancária</a>
        <a href="?module=fechamento">Fechamento</a>
        <a href="?module=relatorios">Relatórios</a>
        <a href="?module=fornecedores">Fornecedores</a>
        <a href="?module=configuracoes">Configurações</a>
    </nav>
</header>
<script>
    function toggleMenu(event, menuId) {
        event.preventDefault();
        event.stopPropagation();
        const menu = document.getElementById(menuId);
        if (!menu) {
            return;
        }
        document.querySelectorAll('.menu-dropdown').forEach((item) => {
            if (item.id !== menuId) {
                item.classList.remove('show');
            }
        });
        menu.classList.toggle('show');
    }

    document.addEventListener('click', () => {
        document.querySelectorAll('.menu-dropdown').forEach((menu) => menu.classList.remove('show'));
    });
</script>
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
            <tr><td><?= dateBr((string) $t['occurred_on']) ?></td><td><?= $t['movement_type'] ?></td><td><?= money((float) $t['amount']) ?></td><td><?= htmlspecialchars($t['category']) ?></td><td><?= htmlspecialchars((string) $t['subcategory']) ?></td><td><?= htmlspecialchars((string) $t['origin_account']) ?></td><td><?= htmlspecialchars((string) $t['destination_account']) ?></td><td><?= htmlspecialchars((string) $t['description']) ?></td></tr>
        <?php endforeach; ?>
    </table>

    <h4>Análise do fluxo por categoria</h4>
    <table>
        <tr><th>Categoria</th><th>Entradas</th><th>Saídas</th><th>Saldo</th></tr>
        <?php foreach ($cashflowByCategory as $row): ?>
            <tr>
                <td><?= htmlspecialchars((string) $row['category']) ?></td>
                <td><?= money((float) $row['entradas']) ?></td>
                <td><?= money((float) $row['saidas']) ?></td>
                <td><?= money((float) $row['saldo']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Análise do fluxo por subcategoria</h4>
    <table>
        <tr><th>Categoria</th><th>Subcategoria</th><th>Entradas</th><th>Saídas</th><th>Saldo</th></tr>
        <?php foreach ($cashflowBySubcategory as $row): ?>
            <tr>
                <td><?= htmlspecialchars((string) $row['category']) ?></td>
                <td><?= htmlspecialchars((string) $row['subcategory']) ?></td>
                <td><?= money((float) $row['entradas']) ?></td>
                <td><?= money((float) $row['saidas']) ?></td>
                <td><?= money((float) $row['saldo']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php elseif ($module === 'pagar'): ?>
    <h3>Contas a Pagar</h3>
    <button type="button" onclick="document.getElementById('payableFilterModal').showModal()">Filtrar</button>
    <a href="?module=pagar">Limpar filtros</a>

    <dialog id="payableFilterModal">
        <form method="get">
            <input type="hidden" name="module" value="pagar">
            <h4>Filtros de Contas a Pagar</h4>
            <label>Vencimento de: <input type="date" name="payable_due_date_from" value="<?= htmlspecialchars($payableDueDateFrom) ?>"></label>
            <label>até: <input type="date" name="payable_due_date_to" value="<?= htmlspecialchars($payableDueDateTo) ?>"></label><br>
            <label>Lançamento de: <input type="date" name="payable_created_date_from" value="<?= htmlspecialchars($payableCreatedDateFrom) ?>"></label>
            <label>até: <input type="date" name="payable_created_date_to" value="<?= htmlspecialchars($payableCreatedDateTo) ?>"></label><br>
            <label>Fornecedor:
                <select name="payable_supplier">
                    <option value="">Todos</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= htmlspecialchars($supplier['name']) ?>" <?= $payableSupplierFilter === (string) $supplier['name'] ? 'selected' : '' ?>><?= htmlspecialchars($supplier['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Situação:
                <select name="payable_status">
                    <option value="">Todas</option>
                    <option value="aberto" <?= $payableStatusFilter === 'aberto' ? 'selected' : '' ?>>aberto</option>
                    <option value="pago" <?= $payableStatusFilter === 'pago' ? 'selected' : '' ?>>pago</option>
                    <option value="atrasado" <?= $payableStatusFilter === 'atrasado' ? 'selected' : '' ?>>atrasado</option>
                </select>
            </label><br>
            <label>Empresa:
                <select name="payable_company">
                    <option value="">Todas</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= htmlspecialchars($company['name']) ?>" <?= $payableCompanyFilter === (string) $company['name'] ? 'selected' : '' ?>><?= htmlspecialchars($company['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Tipo:
                <select name="payable_type_filter">
                    <option value="">Todos</option>
                    <?php foreach ($payableTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type['name']) ?>" <?= $payableTypeFilter === (string) $type['name'] ? 'selected' : '' ?>><?= htmlspecialchars($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            <button>Aplicar filtros</button>
            <button type="button" onclick="document.getElementById('payableFilterModal').close()">Fechar</button>
        </form>
    </dialog>
    <form method="post" id="payableForm">
        <input type="hidden" name="action" id="payable_action" value="<?= $editingPayable ? 'edit' : 'create' ?>">
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
        <?php if ($editingPayable): ?>
            <input name="installment" placeholder="Parcela" value="<?= htmlspecialchars((string) ($editingPayable['installment'] ?? '')) ?>">
        <?php else: ?>
            <select name="installments_count" id="payable_installments_count">
                <?php for ($n = 1; $n <= 24; $n++): ?>
                    <option value="<?= $n ?>"><?= $n ?> parcela<?= $n > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
            <input type="hidden" name="installment" id="payable_installment_label" value="1/1">
            <input type="hidden" name="installments_payload" id="installments_payload" value="">
        <?php endif; ?>
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
    <table><tr><th>Empresa</th><th>Tipo</th><th>Fornecedor</th><th>Boleto</th><th>Vencimento</th><th>Valor</th><th>Parcela</th><th>Situação</th><th>Aviso</th><th>Observação</th><th>Ações</th></tr>
        <?php foreach ($payables as $p): ?>
            <tr>
                <td><?= htmlspecialchars((string) $p['company']) ?></td>
                <td><?= htmlspecialchars((string) $p['payable_type']) ?></td>
                <td><?= htmlspecialchars($p['supplier']) ?></td>
                <td><?= htmlspecialchars((string) $p['boleto_number']) ?></td>
                <td><?= dateBr((string) $p['due_date']) ?></td>
                <td><?= money((float) $p['amount']) ?></td>
                <td><?= htmlspecialchars((string) $p['installment']) ?></td>
                <td><span class="badge <?= $p['display_status'] ?>"><?= $p['display_status'] ?></span></td>
                <td><?= dateBr((string) $p['reminder_date']) ?></td>
                <td><?= htmlspecialchars((string) $p['notes']) ?></td>
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
                    <form method="post" style="display:inline;" onsubmit="return confirmPayableDelete(this)">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="delete_cashflow" value="0">
                        <button class="btn-danger">Excluir</button>
                    </form>
                    <?php if ($p['status'] !== 'pago'): ?>
                        <button type="button" class="btn-success" onclick="openSettleModal(<?= $p['id'] ?>, <?= (float) $p['amount'] ?>)">Dar baixa</button>
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
    <dialog id="installmentsModal">
        <form method="dialog" id="installmentsDialogForm">
            <h4>Parcelamento automático</h4>
            <p class="small">Informe vencimento, número do documento e observação de cada parcela.</p>
            <div id="installmentsContainer"></div>
            <button type="submit" class="btn-success">Salvar parcelas</button>
            <button type="button" onclick="document.getElementById('installmentsModal').close()">Cancelar</button>
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

        function confirmPayableDelete(form) {
            if (!confirm('Excluir este lançamento de contas a pagar?')) {
                return false;
            }
            const removeFromCashflow = confirm('Deseja excluir também o lançamento no fluxo de caixa?');
            const cashflowField = form.querySelector('input[name="delete_cashflow"]');
            if (cashflowField) {
                cashflowField.value = removeFromCashflow ? '1' : '0';
            }
            return true;
        }

        (function () {
            const form = document.getElementById('payableForm');
            const installmentsSelect = document.getElementById('payable_installments_count');
            const installmentLabel = document.getElementById('payable_installment_label');
            const actionField = document.getElementById('payable_action');
            const payloadField = document.getElementById('installments_payload');
            const modal = document.getElementById('installmentsModal');
            const container = document.getElementById('installmentsContainer');
            const baseDueDate = form?.querySelector('input[name="due_date"]');
            const baseBoleto = form?.querySelector('input[name="boleto_number"]');
            const baseNotes = form?.querySelector('input[name="notes"]');
            if (!form || !installmentsSelect || !installmentLabel || !actionField || !payloadField || !modal || !container) {
                return;
            }

            const updateInstallmentLabel = () => {
                const count = parseInt(installmentsSelect.value || '1', 10);
                installmentLabel.value = count > 1 ? `1/${count}` : '1/1';
            };

            const openInstallmentsModal = (count) => {
                const dueDateValue = baseDueDate?.value || '';
                const boletoValue = baseBoleto?.value || '';
                const notesValue = baseNotes?.value || '';
                container.innerHTML = '';
                for (let i = 1; i <= count; i++) {
                    const block = document.createElement('div');
                    block.innerHTML = `
                        <p><strong>Parcela ${i}/${count}</strong></p>
                        <label>Vencimento: <input type="date" data-field="due_date" data-index="${i}" value="${dueDateValue}" required></label>
                        <label>Documento: <input data-field="boleto_number" data-index="${i}" value="${boletoValue}"></label>
                        <label>Observação: <input data-field="notes" data-index="${i}" value="${notesValue}"></label>
                        <hr>
                    `;
                    container.appendChild(block);
                }
                modal.showModal();
            };

            installmentsSelect.addEventListener('change', updateInstallmentLabel);
            updateInstallmentLabel();

            form.addEventListener('submit', (event) => {
                const count = parseInt(installmentsSelect.value || '1', 10);
                if (count <= 2) {
                    actionField.value = 'create';
                    payloadField.value = '';
                    return;
                }
                event.preventDefault();
                openInstallmentsModal(count);
            });

            document.getElementById('installmentsDialogForm').addEventListener('submit', (event) => {
                event.preventDefault();
                const count = parseInt(installmentsSelect.value || '1', 10);
                const payload = [];
                for (let i = 1; i <= count; i++) {
                    const dueDate = container.querySelector(`input[data-field="due_date"][data-index="${i}"]`)?.value || '';
                    const boletoNumber = container.querySelector(`input[data-field="boleto_number"][data-index="${i}"]`)?.value || '';
                    const notes = container.querySelector(`input[data-field="notes"][data-index="${i}"]`)?.value || '';
                    payload.push({ due_date: dueDate, boleto_number: boletoNumber, notes: notes });
                }
                payloadField.value = JSON.stringify(payload);
                actionField.value = 'create_installments';
                modal.close();
                form.submit();
            });
        })();
    </script>
<?php elseif ($module === 'funcionarios'): ?>
    <h3>Módulo de Funcionário</h3>
    <button type="button" onclick="document.getElementById('employeeModal').showModal()">Cadastrar funcionário</button>
    <button type="button" onclick="document.getElementById('employeeDebtModal').showModal()">Cadastrar o que o funcionário deve</button>

    <?php
        $employeesByRole = [];
        foreach ($employees as $employee) {
            $role = (string) ($employee['role'] ?: 'Sem cargo');
            $employeesByRole[$role][] = $employee;
        }
    ?>
    <?php foreach ($employeesByRole as $role => $roleEmployees): ?>
        <h4>Cargo: <?= htmlspecialchars($role) ?></h4>
        <table>
            <tr><th>Funcionário</th><th>Dívida em aberto</th><th>Ações</th></tr>
            <?php foreach ($roleEmployees as $employee): ?>
                <?php
                    $openDebt = array_reduce($employeeDebts, static function (float $carry, array $debt) use ($employee): float {
                        if ((int) $debt['employee_id'] !== (int) $employee['id'] || (string) $debt['status'] !== 'aberto') {
                            return $carry;
                        }
                        return $carry + (float) $debt['amount'];
                    }, 0.0);
                ?>
                <tr>
                    <td><?= htmlspecialchars((string) $employee['name']) ?></td>
                    <td><?= money($openDebt) ?></td>
                    <td>
                        <button
                            type="button"
                            onclick='openEmployeeEditModal(<?= json_encode([
                                'id' => (int) $employee['id'],
                                'name' => (string) $employee['name'],
                                'role' => (string) $employee['role'],
                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            Editar
                        </button>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Excluir funcionário?')">
                            <input type="hidden" name="action" value="employee_delete">
                            <input type="hidden" name="id" value="<?= (int) $employee['id'] ?>">
                            <button class="btn-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>

    <h4>Histórico de lançamentos (o que funcionário deve)</h4>
    <table>
        <tr><th>Data</th><th>Funcionário</th><th>Cargo</th><th>Descrição</th><th>Valor</th><th>Situação</th></tr>
        <?php foreach ($employeeDebts as $debt): ?>
            <tr>
                <td><?= dateBr((string) $debt['debt_date']) ?></td>
                <td><?= htmlspecialchars((string) $debt['employee_name']) ?></td>
                <td><?= htmlspecialchars((string) $debt['employee_role']) ?></td>
                <td><?= htmlspecialchars((string) $debt['description']) ?></td>
                <td><?= money((float) $debt['amount']) ?></td>
                <td><?= htmlspecialchars((string) $debt['status']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <dialog id="employeeModal">
        <form method="post">
            <input type="hidden" name="action" value="employee_add">
            <h4>1. Dados pessoais</h4>
            <input name="full_name" placeholder="Nome completo" required>
            <input name="social_name" placeholder="Nome social">
            <label>Data de nascimento: <input type="date" name="birth_date"></label>
            <input name="gender" placeholder="Sexo">
            <input name="marital_status" placeholder="Estado civil">
            <input name="nationality" placeholder="Nacionalidade">
            <input name="birth_place" placeholder="Naturalidade">
            <input name="mother_name" placeholder="Nome da mãe">
            <input name="father_name" placeholder="Nome do pai">
            <input name="employee_photo" placeholder="Foto do funcionário (URL/caminho)">
            <input name="phone_primary" placeholder="Telefone principal">
            <input name="phone_secondary" placeholder="Telefone secundário">
            <input type="email" name="email" placeholder="E-mail">
            <input name="full_address" placeholder="Endereço completo">
            <input name="cep" placeholder="CEP">
            <input name="street" placeholder="Rua">
            <input name="address_number" placeholder="Número">
            <input name="address_complement" placeholder="Complemento">
            <input name="neighborhood" placeholder="Bairro">
            <input name="city" placeholder="Cidade">
            <input name="state" placeholder="Estado">

            <h4>2. Documentos e RH</h4>
            <input name="cpf" placeholder="CPF">
            <input name="rg" placeholder="RG">
            <input name="rg_issuer" placeholder="Órgão emissor">
            <label>Data emissão RG: <input type="date" name="rg_issue_date"></label>
            <input name="cnh" placeholder="CNH">
            <input name="employee_registration" placeholder="Matrícula do funcionário">
            <input name="work_store" placeholder="Empresa/loja em que trabalha">
            <input name="education" placeholder="Escolaridade">
            <input name="courses" placeholder="Cursos">
            <input name="certifications" placeholder="Certificações">
            <textarea name="dependents_info" placeholder="Dependentes (nome e CPF)"></textarea>
            <input name="emergency_contact_name" placeholder="Nome do contato de emergência">
            <input name="emergency_contact_phone" placeholder="Telefone do contato">
            <input name="emergency_contact_relation" placeholder="Grau de parentesco">
            <input name="admission_exams" placeholder="Exames admissionais">
            <input name="periodic_exams" placeholder="Exames periódicos">
            <input name="epi_usage" placeholder="Uso de EPI">
            <input name="uniform_size" placeholder="Tamanho de uniforme">
            <textarea name="internal_notes" placeholder="Observações internas"></textarea>
            <input name="branch" placeholder="Filial">
            <input name="function_role" placeholder="Função">
            <input name="department" placeholder="Setor/departamento">
            <select name="employment_type">
                <option value="">Tipo de vínculo</option>
                <option value="clt">CLT</option>
                <option value="temporario">Temporário</option>
                <option value="estagio">Estágio</option>
                <option value="jovem_aprendiz">Jovem aprendiz</option>
                <option value="terceirizado">Terceirizado</option>
                <option value="autonomo">Autônomo</option>
            </select>
            <label>Data de admissão: <input type="date" name="admission_date"></label>
            <label>Experiência início: <input type="date" name="experience_start"></label>
            <label>Experiência fim: <input type="date" name="experience_end"></label>
            <input type="number" step="0.01" name="base_salary" placeholder="Salário base">
            <select name="payment_type">
                <option value="">Tipo de pagamento</option>
                <option value="mensal">Mensal</option>
                <option value="quinzenal">Quinzenal</option>
                <option value="semanal">Semanal</option>
                <option value="comissao">Comissão</option>
            </select>
            <input name="work_schedule" placeholder="Jornada de trabalho">
            <input name="entry_time" placeholder="Horário de entrada">
            <input name="exit_time" placeholder="Horário de saída">
            <input name="break_time" placeholder="Intervalo">
            <input name="shift_scale" placeholder="Escala">
            <input name="days_off" placeholder="Dias de folga">
            <select name="employee_status">
                <option value="">Situação do funcionário</option>
                <option value="ativo">Ativo</option>
                <option value="afastado">Afastado</option>
                <option value="ferias">Férias</option>
                <option value="desligado">Desligado</option>
            </select>
            <label>Data de desligamento: <input type="date" name="termination_date"></label>
            <input name="termination_reason" placeholder="Motivo do desligamento">

            <h4>4. Dados financeiros e históricos</h4>
            <input name="bank" placeholder="Banco">
            <input name="agency" placeholder="Agência">
            <input name="account" placeholder="Conta">
            <input name="account_type" placeholder="Tipo de conta">
            <input name="pix_key" placeholder="Chave Pix">
            <input type="number" step="0.01" name="contract_salary" placeholder="Salário contratual">
            <input type="number" step="0.01" name="commission_percent" placeholder="Comissão %">
            <input type="number" step="0.01" name="sales_goal" placeholder="Meta de vendas">
            <input type="number" step="0.01" name="bonus" placeholder="Bonificação">
            <input type="number" step="0.01" name="transport_allowance" placeholder="Vale transporte">
            <input type="number" step="0.01" name="meal_allowance" placeholder="Vale alimentação/refeição">
            <input type="number" step="0.01" name="other_discounts" placeholder="Outros descontos">
            <input type="number" step="0.01" name="other_additions" placeholder="Outros adicionais">
            <input name="cost_center" placeholder="Centro de custo">
            <textarea name="vacation_history" placeholder="Histórico de férias"></textarea>
            <textarea name="salary_history" placeholder="Histórico salarial"></textarea>
            <textarea name="warning_history" placeholder="Histórico de advertências"></textarea>
            <textarea name="promotion_history" placeholder="Histórico de promoções de cargo"></textarea>
            <textarea name="performance_review" placeholder="Avaliação de desempenho"></textarea>
            <textarea name="training_history" placeholder="Histórico de treinamentos"></textarea>
            <textarea name="uniform_history" placeholder="Registro de uniformes entregues"></textarea>
            <textarea name="epi_history" placeholder="Registro de EPIs entregues"></textarea>
            <textarea name="leave_history" placeholder="Histórico de afastamentos/atestados"></textarea>
            <button>Salvar funcionário</button>
            <button type="button" onclick="document.getElementById('employeeModal').close()">Fechar</button>
        </form>
    </dialog>

    <dialog id="employeeDebtModal">
        <form method="post">
            <input type="hidden" name="action" value="debt_add">
            <select name="employee_id" required>
                <option value="">Funcionário</option>
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= (int) $employee['id'] ?>"><?= htmlspecialchars((string) ($employee['name'] . ' - ' . $employee['role'])) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Data: <input type="date" name="debt_date" value="<?= $today ?>" required></label>
            <input name="description" placeholder="Descrição">
            <input type="number" step="0.01" min="0" name="amount" placeholder="Valor" required>
            <select name="status">
                <option value="aberto">aberto</option>
                <option value="quitado">quitado</option>
            </select>
            <button>Salvar lançamento</button>
            <button type="button" onclick="document.getElementById('employeeDebtModal').close()">Fechar</button>
        </form>
    </dialog>
    <dialog id="employeeEditModal">
        <form method="post">
            <input type="hidden" name="action" value="employee_update">
            <input type="hidden" name="id" id="employee_edit_id">
            <input name="name" id="employee_edit_name" placeholder="Nome do funcionário" required>
            <input name="role" id="employee_edit_role" placeholder="Cargo" required>
            <button>Salvar edição</button>
            <button type="button" onclick="document.getElementById('employeeEditModal').close()">Fechar</button>
        </form>
    </dialog>
    <script>
        function openEmployeeEditModal(employee) {
            document.getElementById('employee_edit_id').value = employee.id ?? '';
            document.getElementById('employee_edit_name').value = employee.name ?? '';
            document.getElementById('employee_edit_role').value = employee.role ?? '';
            document.getElementById('employeeEditModal').showModal();
        }
    </script>
<?php elseif ($module === 'veiculos'): ?>
    <h3>Controle de Veículos</h3>
    <button type="button" onclick="document.getElementById('vehicleModal').showModal()">Cadastrar veículo</button>
    <button type="button" onclick="document.getElementById('vehicleExpenseModal').showModal()">Cadastrar despesa/manutenção/abastecimento</button>

    <h4>Veículos cadastrados</h4>
    <table>
        <tr><th>Nome</th><th>Placa</th><th>Modelo</th><th>Ano</th></tr>
        <?php foreach ($vehicles as $vehicle): ?>
            <tr>
                <td><?= htmlspecialchars((string) $vehicle['name']) ?></td>
                <td><?= htmlspecialchars((string) $vehicle['plate']) ?></td>
                <td><?= htmlspecialchars((string) $vehicle['model']) ?></td>
                <td><?= htmlspecialchars((string) $vehicle['year']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Lançamentos de despesas</h4>
    <table>
        <tr><th>Data</th><th>Veículo</th><th>Tipo</th><th>Descrição</th><th>Valor</th></tr>
        <?php foreach ($vehicleExpenses as $expense): ?>
            <tr>
                <td><?= dateBr((string) $expense['expense_date']) ?></td>
                <td><?= htmlspecialchars((string) ($expense['vehicle_name'] . ' ' . ($expense['vehicle_plate'] ? '(' . $expense['vehicle_plate'] . ')' : ''))) ?></td>
                <td><?= htmlspecialchars((string) $expense['expense_type']) ?></td>
                <td><?= htmlspecialchars((string) $expense['description']) ?></td>
                <td><?= money((float) $expense['amount']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <dialog id="vehicleModal">
        <form method="post">
            <input type="hidden" name="action" value="vehicle_add">
            <input name="name" placeholder="Nome do veículo" required>
            <input name="plate" placeholder="Placa">
            <input name="model" placeholder="Modelo">
            <input name="year" placeholder="Ano">
            <button>Salvar veículo</button>
            <button type="button" onclick="document.getElementById('vehicleModal').close()">Fechar</button>
        </form>
    </dialog>

    <dialog id="vehicleExpenseModal">
        <form method="post">
            <input type="hidden" name="action" value="vehicle_expense_add">
            <select name="vehicle_id" required>
                <option value="">Veículo</option>
                <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?= (int) $vehicle['id'] ?>"><?= htmlspecialchars((string) ($vehicle['name'] . ' ' . ($vehicle['plate'] ? '(' . $vehicle['plate'] . ')' : ''))) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Data: <input type="date" name="expense_date" value="<?= $today ?>" required></label>
            <select name="expense_type" required>
                <option value="despesa">despesa</option>
                <option value="manutencao">manutenção</option>
                <option value="abastecimento">abastecimento</option>
            </select>
            <input name="description" placeholder="Descrição">
            <input type="number" step="0.01" min="0" name="amount" placeholder="Valor" required>
            <button>Salvar lançamento</button>
            <button type="button" onclick="document.getElementById('vehicleExpenseModal').close()">Fechar</button>
        </form>
    </dialog>
<?php elseif ($module === 'recebimento_clientes'): ?>
    <h3>Recebimento de Clientes</h3>
    <form method="post" id="customerReceiptForm">
        <label>Data: <input type="date" name="receipt_date" value="<?= $today ?>" required></label>
        <input name="customer_name" placeholder="Nome do cliente" required>
        <input type="number" step="0.01" min="0" name="total_amount" id="receipt_total_amount" placeholder="Valor total" required>
        <input type="number" step="0.01" min="0" name="discount" id="receipt_discount" placeholder="Desconto" value="0">
        <input type="number" step="0.01" min="0" name="interest" id="receipt_interest" placeholder="Juros" value="0">
        <input type="number" step="0.01" min="0" id="receipt_net_amount" placeholder="Valor líquido" readonly>
        <select name="payment_method" required>
            <option value="">Forma de pagamento</option>
            <?php foreach ($paymentMethods as $method): ?>
                <option value="<?= htmlspecialchars($method['name']) ?>"><?= htmlspecialchars($method['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button>Salvar recebimento</button>
    </form>
    <table>
        <tr><th>Data</th><th>Cliente</th><th>Total</th><th>Desconto</th><th>Juros</th><th>Líquido</th><th>Forma de pagamento</th></tr>
        <?php foreach ($customerReceipts as $receipt): ?>
            <tr>
                <td><?= dateBr((string) $receipt['receipt_date']) ?></td>
                <td><?= htmlspecialchars((string) $receipt['customer_name']) ?></td>
                <td><?= money((float) $receipt['total_amount']) ?></td>
                <td><?= money((float) $receipt['discount']) ?></td>
                <td><?= money((float) $receipt['interest']) ?></td>
                <td><?= money((float) $receipt['net_amount']) ?></td>
                <td><?= htmlspecialchars((string) $receipt['payment_method']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <script>
        (function () {
            const total = document.getElementById('receipt_total_amount');
            const discount = document.getElementById('receipt_discount');
            const interest = document.getElementById('receipt_interest');
            const net = document.getElementById('receipt_net_amount');
            const update = () => {
                const totalValue = parseFloat(total?.value || '0') || 0;
                const discountValue = parseFloat(discount?.value || '0') || 0;
                const interestValue = parseFloat(interest?.value || '0') || 0;
                const netValue = Math.max(0, totalValue - discountValue + interestValue);
                net.value = netValue.toFixed(2);
            };
            total?.addEventListener('input', update);
            discount?.addEventListener('input', update);
            interest?.addEventListener('input', update);
            update();
        })();
    </script>
<?php elseif ($module === 'vendas_frente_caixa'): ?>
    <h3>Vendas Frente de Caixa</h3>
    <button type="button" onclick="openFrontCashModal()">Lançamento</button>
    <button type="button" onclick="document.getElementById('frontCashFilterModal').showModal()">Filtrar</button>
    <button type="button" class="btn-success" onclick="openGasSaleModal()">Vendas de Gás</button>

    <dialog id="frontCashFilterModal">
        <form method="get">
            <input type="hidden" name="module" value="vendas_frente_caixa">
            <h4>Filtrar lançamentos</h4>
            <label>Data inicial: <input type="date" name="front_cash_date_from" value="<?= htmlspecialchars($frontCashFilters['date_from']) ?>"></label>
            <label>Data final: <input type="date" name="front_cash_date_to" value="<?= htmlspecialchars($frontCashFilters['date_to']) ?>"></label>
            <select name="front_cash_register">
                <option value="">Todos os caixas</option>
                <?php foreach ($frontCashRegisters as $register): ?>
                    <option value="<?= htmlspecialchars((string) $register['name']) ?>" <?= $frontCashFilters['cash_register'] === (string) $register['name'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $register['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="front_cash_location">
                <option value="">Todos os locais</option>
                <option value="Caixa Loja" <?= $frontCashFilters['sale_location'] === 'Caixa Loja' ? 'selected' : '' ?>>Caixa Loja</option>
                <option value="Caixa Parafuso" <?= $frontCashFilters['sale_location'] === 'Caixa Parafuso' ? 'selected' : '' ?>>Caixa Parafuso</option>
            </select>
            <select name="front_cash_payment_method">
                <option value="">Todas as formas</option>
                <option value="Dinheiro" <?= $frontCashFilters['payment_method'] === 'Dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                <option value="Cheque" <?= $frontCashFilters['payment_method'] === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
                <option value="Cartão Débito" <?= $frontCashFilters['payment_method'] === 'Cartão Débito' ? 'selected' : '' ?>>Cartão débito</option>
                <option value="Cartão Crédito" <?= $frontCashFilters['payment_method'] === 'Cartão Crédito' ? 'selected' : '' ?>>Cartão crédito</option>
                <option value="Cartão Parcelado" <?= $frontCashFilters['payment_method'] === 'Cartão Parcelado' ? 'selected' : '' ?>>Cartão parcelado</option>
                <option value="Pix e TED" <?= $frontCashFilters['payment_method'] === 'Pix e TED' ? 'selected' : '' ?>>Pix e TED</option>
                <option value="Pix QRCode" <?= $frontCashFilters['payment_method'] === 'Pix QRCode' ? 'selected' : '' ?>>Pix QRCode</option>
            </select>
            <button>Aplicar filtro</button>
            <a href="?module=vendas_frente_caixa">Limpar</a>
            <button type="button" onclick="document.getElementById('frontCashFilterModal').close()">Fechar</button>
        </form>
    </dialog>

    <dialog id="frontCashModal">
        <form method="post" id="frontCashForm">
            <input type="hidden" name="action" value="create" id="front_cash_action">
            <input type="hidden" name="id" value="" id="front_cash_id">
            <h4 id="front_cash_modal_title">Novo lançamento</h4>
            <label>Data: <input type="date" name="sale_date" id="front_cash_sale_date" value="<?= $today ?>" required></label>
            <select name="cash_register" id="front_cash_register" required>
                <option value="">Caixa</option>
                <?php foreach ($frontCashRegisters as $register): ?>
                    <option value="<?= htmlspecialchars((string) $register['name']) ?>"><?= htmlspecialchars((string) $register['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="sale_location" id="front_cash_location" required>
                <option value="">Local</option>
                <option value="Caixa Loja">Caixa Loja</option>
                <option value="Caixa Parafuso">Caixa Parafuso</option>
            </select>
            <select name="payment_method" id="front_cash_payment_method" required>
                <option value="">Forma de pagamento</option>
                <option value="Dinheiro">Dinheiro</option>
                <option value="Cheque">Cheque</option>
                <option value="Cartão Débito">Cartão débito</option>
                <option value="Cartão Crédito">Cartão crédito</option>
                <option value="Cartão Parcelado">Cartão parcelado</option>
                <option value="Pix e TED">Pix e TED</option>
                <option value="Pix QRCode">Pix QRCode</option>
            </select>
            <input type="number" step="0.01" min="0" name="amount" id="front_cash_amount" placeholder="Valor" required>
            <button id="front_cash_submit">Salvar lançamento</button>
            <button type="button" onclick="document.getElementById('frontCashModal').close()">Fechar</button>
        </form>
    </dialog>

    <dialog id="gasSaleModal">
        <form method="post" id="gasSaleForm">
            <input type="hidden" name="action" value="gas_create" id="gas_sale_action">
            <input type="hidden" name="id" id="gas_sale_id">
            <h4 id="gas_sale_modal_title">Venda de Gás</h4>
            <label>Data: <input type="date" name="gas_sale_date" id="gas_sale_date" value="<?= $today ?>" required></label>
            <select name="seller" id="gas_sale_seller" required>
                <option value="">Vendedor</option>
                <?php foreach ($frontCashVendors as $vendor): ?>
                    <option value="<?= htmlspecialchars((string) $vendor['name']) ?>"><?= htmlspecialchars((string) $vendor['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" min="0" name="qty_refill" id="gas_qty_refill" placeholder="Quant. recarga" value="0" required>
            <input type="number" min="0" name="qty_full" id="gas_qty_full" placeholder="Quant. gás completo" value="0" required>
            <button id="gas_sale_submit">Salvar venda de gás</button>
            <button type="button" onclick="document.getElementById('gasSaleModal').close()">Fechar</button>
        </form>
    </dialog>
    <table>
        <tr><th>Data</th><th>Caixa</th><th>Local</th><th>Forma de pagamento</th><th>Valor</th><th>Ações</th></tr>
        <?php foreach ($frontCashSales as $sale): ?>
            <tr>
                <td><?= dateBr((string) $sale['sale_date']) ?></td>
                <td><?= htmlspecialchars((string) $sale['cash_register']) ?></td>
                <td><?= htmlspecialchars((string) $sale['sale_location']) ?></td>
                <td><?= htmlspecialchars((string) $sale['payment_method']) ?></td>
                <td><?= money((float) $sale['amount']) ?></td>
                <td>
                    <button type="button" onclick='editFrontCash(<?= json_encode($sale, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Editar</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Excluir lançamento?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $sale['id'] ?>">
                        <button class="btn-danger">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h4>Lançamentos de Vendas de Gás</h4>
    <table>
        <tr><th>Data</th><th>Vendedor</th><th>Recarga</th><th>Gás completo</th><th>Ações</th></tr>
        <?php foreach ($frontCashGasSales as $gasSale): ?>
            <tr>
                <td><?= dateBr((string) $gasSale['sale_date']) ?></td>
                <td><?= htmlspecialchars((string) $gasSale['seller']) ?></td>
                <td><?= (int) $gasSale['qty_refill'] ?></td>
                <td><?= (int) $gasSale['qty_full'] ?></td>
                <td>
                    <button type="button" onclick='editGasSale(<?= json_encode($gasSale, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Editar</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Excluir venda de gás?')">
                        <input type="hidden" name="action" value="gas_delete">
                        <input type="hidden" name="id" value="<?= (int) $gasSale['id'] ?>">
                        <button class="btn-danger">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <script>
        function openFrontCashModal() {
            document.getElementById('front_cash_action').value = 'create';
            document.getElementById('front_cash_id').value = '';
            document.getElementById('front_cash_modal_title').textContent = 'Novo lançamento';
            document.getElementById('front_cash_submit').textContent = 'Salvar lançamento';
            document.getElementById('frontCashForm').reset();
            document.getElementById('front_cash_sale_date').value = '<?= $today ?>';
            document.getElementById('frontCashModal').showModal();
        }
        function editFrontCash(sale) {
            document.getElementById('front_cash_action').value = 'edit';
            document.getElementById('front_cash_id').value = sale.id || '';
            document.getElementById('front_cash_modal_title').textContent = 'Editar lançamento';
            document.getElementById('front_cash_submit').textContent = 'Salvar edição';
            document.getElementById('front_cash_sale_date').value = sale.sale_date || '';
            document.getElementById('front_cash_register').value = sale.cash_register || '';
            document.getElementById('front_cash_location').value = sale.sale_location || '';
            document.getElementById('front_cash_payment_method').value = sale.payment_method || '';
            document.getElementById('front_cash_amount').value = sale.amount || '';
            document.getElementById('frontCashModal').showModal();
        }
        function openGasSaleModal() {
            document.getElementById('gas_sale_action').value = 'gas_create';
            document.getElementById('gas_sale_id').value = '';
            document.getElementById('gas_sale_modal_title').textContent = 'Venda de Gás';
            document.getElementById('gas_sale_submit').textContent = 'Salvar venda de gás';
            document.getElementById('gasSaleForm').reset();
            document.getElementById('gas_sale_date').value = '<?= $today ?>';
            document.getElementById('gas_qty_refill').value = '0';
            document.getElementById('gas_qty_full').value = '0';
            document.getElementById('gasSaleModal').showModal();
        }
        function editGasSale(sale) {
            document.getElementById('gas_sale_action').value = 'gas_edit';
            document.getElementById('gas_sale_id').value = sale.id || '';
            document.getElementById('gas_sale_modal_title').textContent = 'Editar venda de gás';
            document.getElementById('gas_sale_submit').textContent = 'Salvar edição';
            document.getElementById('gas_sale_date').value = sale.sale_date || '';
            document.getElementById('gas_sale_seller').value = sale.seller || '';
            document.getElementById('gas_qty_refill').value = sale.qty_refill || 0;
            document.getElementById('gas_qty_full').value = sale.qty_full || 0;
            document.getElementById('gasSaleModal').showModal();
        }
    </script>
<?php elseif ($module === 'saida_financeiro'): ?>
    <h3>Saída</h3>
    <form method="post">
        <label>Data: <input type="date" name="expense_date" value="<?= $today ?>" required></label>
        <input name="name" placeholder="Nome" required>
        <input type="number" step="0.01" min="0" name="amount" placeholder="Valor" required>
        <select name="payment_method" required>
            <option value="">Forma de pagamento</option>
            <?php foreach ($paymentMethods as $method): ?>
                <option value="<?= htmlspecialchars($method['name']) ?>"><?= htmlspecialchars($method['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button>Salvar saída</button>
    </form>
    <table>
        <tr><th>Data</th><th>Nome</th><th>Valor</th><th>Forma de pagamento</th></tr>
        <?php foreach ($financeExpenses as $expense): ?>
            <tr>
                <td><?= dateBr((string) $expense['expense_date']) ?></td>
                <td><?= htmlspecialchars((string) $expense['name']) ?></td>
                <td><?= money((float) $expense['amount']) ?></td>
                <td><?= htmlspecialchars((string) $expense['payment_method']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php elseif ($module === 'vendas_prazo'): ?>
    <h3>Vendas a Prazo</h3>
    <form method="post" id="creditSalesForm">
        <label>Data: <input type="date" name="sale_date" value="<?= $today ?>" required></label>
        <label>Empresa/Local:
            <select name="sale_location" required>
                <option value="">Selecionar local</option>
                <?php foreach ($saleLocations as $location): ?>
                    <option value="<?= htmlspecialchars($location['name']) ?>"><?= htmlspecialchars($location['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <input type="number" step="0.01" min="0" name="total_amount" id="credit_total_amount" placeholder="Valor total" required>
        <input type="number" step="0.01" min="0" name="return_on_credit" id="credit_return_on_credit" placeholder="Devolução a prazo" value="0">
        <input type="number" step="0.01" min="0" name="return_exchange_credit" id="credit_return_exchange" placeholder="Devolução troca ou crédito" value="0">
        <input type="number" step="0.01" min="0" id="credit_net_amount" placeholder="Valor líquido" readonly>
        <button>Salvar lançamento</button>
    </form>
    <table>
        <tr><th>Data</th><th>Local</th><th>Valor total</th><th>Devolução a prazo</th><th>Devolução troca/crédito</th><th>Valor líquido</th></tr>
        <?php foreach ($creditSalesTotals as $sale): ?>
            <tr>
                <td><?= dateBr((string) $sale['sale_date']) ?></td>
                <td><?= htmlspecialchars((string) ($sale['sale_location'] ?: 'Sem local')) ?></td>
                <td><?= money((float) $sale['total_amount']) ?></td>
                <td><?= money((float) $sale['return_on_credit']) ?></td>
                <td><?= money((float) $sale['return_exchange_credit']) ?></td>
                <td><?= money((float) $sale['net_amount']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <script>
        (function () {
            const total = document.getElementById('credit_total_amount');
            const returnOnCredit = document.getElementById('credit_return_on_credit');
            const returnExchange = document.getElementById('credit_return_exchange');
            const net = document.getElementById('credit_net_amount');
            const update = () => {
                const totalValue = parseFloat(total?.value || '0') || 0;
                const returnOnCreditValue = parseFloat(returnOnCredit?.value || '0') || 0;
                const returnExchangeValue = parseFloat(returnExchange?.value || '0') || 0;
                const netValue = Math.max(0, totalValue - returnOnCreditValue - returnExchangeValue);
                net.value = netValue.toFixed(2);
            };
            total?.addEventListener('input', update);
            returnOnCredit?.addEventListener('input', update);
            returnExchange?.addEventListener('input', update);
            update();
        })();
    </script>
<?php elseif ($module === 'clientes_atraso'): ?>
    <h3>Clientes em Atraso</h3>
    <button type="button" onclick="document.getElementById('overdueFilterModal').showModal()">Filtrar</button>
    <a href="?module=clientes_atraso">Limpar filtros</a>

    <dialog id="overdueFilterModal">
        <form method="get">
            <input type="hidden" name="module" value="clientes_atraso">
            <label>Data de cobrança de: <input type="date" name="overdue_date_from" value="<?= htmlspecialchars($overdueDateFrom) ?>"></label>
            <label>até: <input type="date" name="overdue_date_to" value="<?= htmlspecialchars($overdueDateTo) ?>"></label><br>
            <label>Situação:
                <select name="overdue_status">
                    <option value="">Todas</option>
                    <option value="vencido" <?= $overdueStatusFilter === 'vencido' ? 'selected' : '' ?>>vencido</option>
                    <option value="spc" <?= $overdueStatusFilter === 'spc' ? 'selected' : '' ?>>spc</option>
                    <option value="outra" <?= $overdueStatusFilter === 'outra' ? 'selected' : '' ?>>outra</option>
                </select>
            </label>
            <label>Nome do cliente: <input name="overdue_name" value="<?= htmlspecialchars($overdueNameFilter) ?>"></label>
            <label>Ordenação:
                <select name="overdue_sort">
                    <option value="date_desc" <?= $overdueSort === 'date_desc' ? 'selected' : '' ?>>Data mais recente</option>
                    <option value="amount_desc" <?= $overdueSort === 'amount_desc' ? 'selected' : '' ?>>Valor maior para menor</option>
                </select>
            </label>
            <button>Aplicar filtros</button>
            <button type="button" onclick="document.getElementById('overdueFilterModal').close()">Fechar</button>
        </form>
    </dialog>

    <form method="post">
        <input type="hidden" name="action" value="create">
        <label>Data que entrou para cobrança: <input type="date" name="collection_entry_date" value="<?= $today ?>" required></label>
        <input name="customer_name" placeholder="Nome do cliente" required>
        <input type="number" step="0.01" min="0" name="amount" placeholder="Valor" required>
        <select name="status" required>
            <option value="vencido">vencido</option>
            <option value="spc">spc</option>
            <option value="outra">outra</option>
        </select>
        <button>Salvar</button>
    </form>
    <table>
        <tr><th>Data cobrança</th><th>Cliente</th><th>Valor</th><th>Dias de atraso</th><th>Situação</th><th>Ações</th></tr>
        <?php foreach ($overdueCustomers as $item): ?>
            <?php
                $entryTime = strtotime((string) $item['collection_entry_date']);
                $todayTime = strtotime($today);
                $daysOverdue = ($entryTime && $todayTime) ? max(0, (int) floor(($todayTime - $entryTime) / 86400)) : 0;
            ?>
            <tr>
                <td><?= dateBr((string) $item['collection_entry_date']) ?></td>
                <td><?= htmlspecialchars((string) $item['customer_name']) ?></td>
                <td><?= money((float) $item['amount']) ?></td>
                <td><?= $daysOverdue ?> dia(s)</td>
                <td><?= htmlspecialchars((string) $item['status']) ?></td>
                <td>
                    <button type="button" onclick="openOverdueInfoModal(<?= json_encode((string) $item['customer_name'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)">Informações</button>
                    <button
                        type="button"
                        onclick='openOverdueEditModal(<?= json_encode([
                            'id' => (int) $item['id'],
                            'collection_entry_date' => (string) $item['collection_entry_date'],
                            'customer_name' => (string) $item['customer_name'],
                            'amount' => (float) $item['amount'],
                            'status' => (string) $item['status'],
                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                        Editar
                    </button>
                    <button type="button" class="btn-success" onclick="openOverdueSettleModal(<?= (int) $item['id'] ?>, <?= (float) $item['amount'] ?>)">Dar baixa</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <dialog id="overdueEditModal">
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="overdue_edit_id">
            <label>Data que entrou para cobrança: <input type="date" name="collection_entry_date" id="overdue_edit_date" required></label><br>
            <input name="customer_name" id="overdue_edit_customer" placeholder="Nome do cliente" required><br>
            <input type="number" step="0.01" min="0" name="amount" id="overdue_edit_amount" placeholder="Valor" required><br>
            <select name="status" id="overdue_edit_status" required>
                <option value="vencido">vencido</option>
                <option value="spc">spc</option>
                <option value="outra">outra</option>
            </select><br>
            <button>Salvar edição</button>
            <button type="button" onclick="document.getElementById('overdueEditModal').close()">Fechar</button>
        </form>
    </dialog>
    <dialog id="overdueSettleModal">
        <form method="post">
            <input type="hidden" name="action" value="settle">
            <input type="hidden" name="id" id="overdue_settle_id">
            <label>Data de pagamento: <input type="date" name="payment_date" value="<?= $today ?>" required></label><br>
            <label>Juros: <input type="number" step="0.01" min="0" name="interest" value="0"></label><br>
            <label>Desconto: <input type="number" step="0.01" min="0" name="discount" value="0"></label><br>
            <label>Valor total (opcional): <input type="number" step="0.01" min="0" name="total_paid"></label><br>
            <label>Forma de pagamento:
                <select name="payment_method" id="overdue_payment_method" required>
                    <option value="">Selecionar</option>
                    <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?= htmlspecialchars($method['name']) ?>"><?= htmlspecialchars($method['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div id="overdue_card_fields" style="display:none;">
                <h4>Lançamento no Controle de Cartões</h4>
                <select name="card_machine" id="overdue_card_machine">
                    <option value="">Máquina</option>
                    <?php foreach ($cardMachines as $machine): ?>
                        <option value="<?= htmlspecialchars($machine['name']) ?>"><?= htmlspecialchars($machine['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="card_brand" id="overdue_card_brand">
                    <option value="">Bandeira</option>
                    <?php foreach ($cardBrands as $brand): ?>
                        <option value="<?= htmlspecialchars($brand['name']) ?>"><?= htmlspecialchars($brand['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="card_type" id="overdue_card_type">
                    <option value="">Forma cartão</option>
                    <?php foreach ($cardPaymentConfigs as $config): ?>
                        <option value="<?= htmlspecialchars($config['name']) ?>"><?= htmlspecialchars($config['name']) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <input type="number" step="0.01" min="0" name="card_fee_percent" id="overdue_card_fee" placeholder="Taxa %">
                <select name="card_sale_location" id="overdue_card_sale_location">
                    <option value="">Local da venda</option>
                    <?php foreach ($saleLocations as $location): ?>
                        <option value="<?= htmlspecialchars($location['name']) ?>"><?= htmlspecialchars($location['name']) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <label>Data da venda: <input type="date" name="card_sale_date" id="overdue_card_sale_date" value="<?= $today ?>"></label>
                <label>Data liberação: <input type="date" name="card_expected_release_date" id="overdue_card_release_date" value="<?= $today ?>"></label>
                <label><input type="checkbox" name="card_received" id="overdue_card_received"> Baixa imediata no cartão</label>
            </div>
            <p class="small">Valor atual em atraso: <span id="overdue_settle_amount">R$ 0,00</span></p>
            <p class="small">Se não informar o valor total, o sistema baixa usando valor devido com juros/desconto.</p>
            <button>Confirmar baixa</button>
            <button type="button" onclick="document.getElementById('overdueSettleModal').close()">Fechar</button>
        </form>
    </dialog>
    <dialog id="overdueInfoModal">
        <div>
            <h4 id="overdue_info_title">Histórico de pagamentos</h4>
            <table>
                <thead><tr><th>Data</th><th>Valor</th><th>Forma de pagamento</th></tr></thead>
                <tbody id="overdue_info_body"></tbody>
            </table>
            <button type="button" onclick="document.getElementById('overdueInfoModal').close()">Fechar</button>
        </div>
    </dialog>
    <script>
        const customerReceiptHistory = <?= json_encode(array_map(static fn(array $row): array => [
            'date' => (string) $row['receipt_date'],
            'customer_name' => (string) $row['customer_name'],
            'amount' => (float) $row['net_amount'],
            'payment_method' => (string) $row['payment_method'],
        ], $customerReceipts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        function openOverdueSettleModal(id, amount) {
            document.getElementById('overdue_settle_id').value = id;
            document.getElementById('overdue_settle_amount').textContent = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(amount);
            document.getElementById('overdueSettleModal').showModal();
        }

        (function () {
            const paymentMethod = document.getElementById('overdue_payment_method');
            const cardFields = document.getElementById('overdue_card_fields');
            const cardInputs = [
                document.getElementById('overdue_card_machine'),
                document.getElementById('overdue_card_brand'),
                document.getElementById('overdue_card_type'),
                document.getElementById('overdue_card_fee'),
                document.getElementById('overdue_card_sale_location'),
                document.getElementById('overdue_card_sale_date'),
                document.getElementById('overdue_card_release_date'),
            ];
            if (!paymentMethod || !cardFields) return;
            const toggleCardFields = () => {
                const value = (paymentMethod.value || '').toLowerCase();
                const isCard = value.includes('cart');
                cardFields.style.display = isCard ? 'block' : 'none';
                for (const input of cardInputs) {
                    if (!input) continue;
                    if (isCard) {
                        if (input.id !== 'overdue_card_fee') {
                            input.setAttribute('required', 'required');
                        }
                    } else {
                        input.removeAttribute('required');
                    }
                }
            };
            paymentMethod.addEventListener('change', toggleCardFields);
            toggleCardFields();
        })();

        function openOverdueEditModal(data) {
            document.getElementById('overdue_edit_id').value = data.id ?? '';
            document.getElementById('overdue_edit_date').value = data.collection_entry_date ?? '';
            document.getElementById('overdue_edit_customer').value = data.customer_name ?? '';
            document.getElementById('overdue_edit_amount').value = data.amount ?? 0;
            document.getElementById('overdue_edit_status').value = data.status ?? 'vencido';
            document.getElementById('overdueEditModal').showModal();
        }

        function openOverdueInfoModal(customerName) {
            const dialog = document.getElementById('overdueInfoModal');
            const title = document.getElementById('overdue_info_title');
            const body = document.getElementById('overdue_info_body');
            if (!dialog || !title || !body) return;
            title.textContent = `Histórico de pagamentos - ${customerName}`;
            const normalizedCustomer = String(customerName || '').trim().toLowerCase();
            const rows = customerReceiptHistory
                .filter((row) => String(row.customer_name || '').trim().toLowerCase() === normalizedCustomer)
                .sort((a, b) => String(b.date).localeCompare(String(a.date)));
            body.innerHTML = '';
            if (rows.length === 0) {
                body.innerHTML = '<tr><td colspan="3">Sem pagamentos registrados.</td></tr>';
            } else {
                for (const row of rows) {
                    const tr = document.createElement('tr');
                    const formattedAmount = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(row.amount || 0);
                    const dateParts = String(row.date || '').split('-');
                    const formattedDate = dateParts.length === 3 ? `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}` : String(row.date || '');
                    tr.innerHTML = `<td>${formattedDate}</td><td>${formattedAmount}</td><td>${row.payment_method || ''}</td>`;
                    body.appendChild(tr);
                }
            }
            dialog.showModal();
        }
    </script>
<?php elseif ($module === 'vendas'): ?>
    <h3>Vendas</h3>
    <div class="cards">
        <div class="card"><h4>Resumo Diário</h4><p><?= money($salesDailyTotal) ?></p></div>
        <div class="card"><h4>Resumo Semanal</h4><p><?= money($salesWeeklyTotal) ?></p></div>
        <div class="card"><h4>Resumo Mensal</h4><p><?= money($salesMonthlyTotal) ?></p></div>
    </div>

    <h4>Vendas do dia (<?= dateBr($today) ?>)</h4>
    <table>
        <tr><th>Data</th><th>Local</th><th>Máquina</th><th>Bandeira</th><th>Tipo</th><th>Bruto</th><th>Líquido</th><th>Status</th></tr>
        <?php foreach ($salesToday as $sale): ?>
            <tr>
                <td><?= dateBr((string) $sale['sale_date']) ?></td>
                <td><?= htmlspecialchars((string) ($sale['sale_location'] ?: 'Sem local')) ?></td>
                <td><?= htmlspecialchars((string) $sale['machine']) ?></td>
                <td><?= htmlspecialchars((string) $sale['brand']) ?></td>
                <td><?= htmlspecialchars((string) $sale['card_type']) ?></td>
                <td><?= money((float) $sale['gross_value']) ?></td>
                <td><?= money((float) $sale['net_value']) ?></td>
                <td><?= (int) $sale['canceled'] ? 'Cancelada' : ((int) $sale['received'] ? 'Recebida' : 'A receber') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Resumo diário por local</h4>
    <table>
        <tr><th>Local</th><th>Qtd vendas</th><th>Total bruto</th><th>Total líquido</th></tr>
        <?php foreach ($salesByLocationToday as $row): ?>
            <tr>
                <td><?= htmlspecialchars((string) $row['sale_location']) ?></td>
                <td><?= (int) $row['total_sales'] ?></td>
                <td><?= money((float) $row['gross_total']) ?></td>
                <td><?= money((float) $row['net_total']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php elseif ($module === 'cartoes'): ?>
    <h3>Controle de Cartões</h3>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <select name="machine" id="card_machine_select" required>
            <option value="">Máquina</option>
            <?php foreach ($cardMachines as $machine): ?>
                <option value="<?= htmlspecialchars($machine['name']) ?>" data-id="<?= $machine['id'] ?>"><?= htmlspecialchars($machine['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="brand" id="card_brand_select" required>
            <option value="">Bandeira</option>
            <?php foreach ($cardBrands as $brand): ?>
                <option value="<?= htmlspecialchars($brand['name']) ?>" data-id="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="card_type" id="card_type_select" required>
            <option value="">Forma de pagamento</option>
            <?php foreach ($cardPaymentConfigs as $config): ?>
                <option
                    value="<?= htmlspecialchars($config['name']) ?>"
                    data-id="<?= $config['id'] ?>"
                    data-fee="<?= (float) $config['fee_percent'] ?>"
                    data-days="<?= (int) $config['release_days'] ?>">
                    <?= htmlspecialchars($config['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input name="fee_percent" id="card_fee_percent" type="number" step="0.01" placeholder="Taxa %" required>
        <select name="sale_location" required>
            <option value="">Local da venda</option>
            <?php foreach ($saleLocations as $location): ?>
                <option value="<?= htmlspecialchars($location['name']) ?>"><?= htmlspecialchars($location['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="gross_value" type="number" step="0.01" placeholder="Valor bruto" required>
        <input name="sale_date" id="card_sale_date" type="date" required><input name="expected_release_date" id="card_expected_release_date" type="date" required>
        <label><input type="checkbox" name="received"> Baixa quando receber</label>
        <button>Salvar</button>
    </form>
    <table><tr><th>Máquina</th><th>Bandeira</th><th>Tipo</th><th>Local</th><th>Taxa</th><th>Bruto</th><th>Líquido</th><th>Venda</th><th>Liberação</th><th>Recebido</th><th>Ações</th></tr>
        <?php foreach ($cards as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['machine']) ?></td>
                <td><?= htmlspecialchars($c['brand']) ?></td>
                <td><?= $c['card_type'] ?></td>
                <td><?= htmlspecialchars((string) $c['sale_location']) ?></td>
                <td><?= $c['fee_percent'] ?>%</td>
                <td><?= money((float) $c['gross_value']) ?></td>
                <td><?= money((float) $c['net_value']) ?></td>
                <td><?= dateBr((string) $c['sale_date']) ?></td>
                <td><?= dateBr((string) $c['expected_release_date']) ?></td>
                <td><?= (int) $c['canceled'] ? 'Cancelado' : ($c['received'] ? 'Sim' : 'Não') ?></td>
                <td>
                    <button type="button" onclick='openCardEditModal(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Editar</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Excluir lançamento de cartão?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button>Excluir</button>
                    </form>
                    <?php if (!(int) $c['received'] && !(int) $c['canceled']): ?>
                        <button type="button" class="btn-success" onclick="openCardSettleModal(<?= (int) $c['id'] ?>)">Dar baixa</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <dialog id="cardEditModal">
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="card_edit_id">
            <select name="machine" id="card_edit_machine" required>
                <?php foreach ($cardMachines as $machine): ?><option value="<?= htmlspecialchars($machine['name']) ?>"><?= htmlspecialchars($machine['name']) ?></option><?php endforeach; ?>
            </select>
            <select name="brand" id="card_edit_brand" required>
                <?php foreach ($cardBrands as $brand): ?><option value="<?= htmlspecialchars($brand['name']) ?>"><?= htmlspecialchars($brand['name']) ?></option><?php endforeach; ?>
            </select>
            <select name="card_type" id="card_edit_type" required>
                <?php foreach ($cardPaymentConfigs as $config): ?><option value="<?= htmlspecialchars($config['name']) ?>"><?= htmlspecialchars($config['name']) ?></option><?php endforeach; ?>
            </select>
            <select name="sale_location" id="card_edit_sale_location" required>
                <?php foreach ($saleLocations as $location): ?><option value="<?= htmlspecialchars($location['name']) ?>"><?= htmlspecialchars($location['name']) ?></option><?php endforeach; ?>
            </select>
            <input name="fee_percent" id="card_edit_fee" type="number" step="0.01" required>
            <input name="gross_value" id="card_edit_gross" type="number" step="0.01" required>
            <input name="sale_date" id="card_edit_sale" type="date" required>
            <input name="expected_release_date" id="card_edit_release" type="date" required>
            <label><input type="checkbox" name="received" id="card_edit_received"> Recebido</label>
            <button>Salvar edição</button>
            <button type="button" onclick="document.getElementById('cardEditModal').close()">Fechar</button>
        </form>
    </dialog>

    <dialog id="cardSettleModal">
        <form method="post">
            <input type="hidden" name="action" value="settle">
            <input type="hidden" name="id" id="card_settle_id">
            <label>Data recebimento: <input name="received_on" type="date" value="<?= $today ?>" required></label>
            <label>Taxa por antecipação (%): <input name="anticipation_fee_percent" type="number" step="0.01" min="0" value="0"></label>
            <label>Categoria:
                <select name="settle_category">
                    <option value="">cartoes_recebidos</option>
                    <?php foreach ($categories as $category): ?><option value="<?= htmlspecialchars($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Subcategoria:
                <select name="settle_subcategory">
                    <option value="">automática</option>
                    <?php foreach ($subcategories as $subcategory): ?><option value="<?= htmlspecialchars($subcategory['name']) ?>"><?= htmlspecialchars($subcategory['parent_name'] . ' > ' . $subcategory['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Conta destino:
                <select name="destination_account">
                    <option value="caixa">Caixa</option>
                    <?php foreach ($banks as $bank): ?><option value="<?= htmlspecialchars($bank['name']) ?>"><?= htmlspecialchars($bank['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label><input type="checkbox" name="canceled"> Cancelamento de cartão</label>
            <button>Confirmar baixa</button>
            <button type="button" onclick="document.getElementById('cardSettleModal').close()">Fechar</button>
        </form>
    </dialog>
    <script>
        (function () {
            const machineSelect = document.getElementById('card_machine_select');
            const brandSelect = document.getElementById('card_brand_select');
            const paymentType = document.getElementById('card_type_select');
            const feeField = document.getElementById('card_fee_percent');
            const saleDateField = document.getElementById('card_sale_date');
            const releaseDateField = document.getElementById('card_expected_release_date');
            const rateRules = <?= json_encode(array_map(static fn(array $r): array => [
                'machine_id' => (int) $r['machine_id'],
                'brand_id' => (int) $r['brand_id'],
                'payment_config_id' => (int) $r['payment_config_id'],
                'fee_percent' => (float) $r['fee_percent'],
                'release_days' => (int) $r['release_days'],
            ], $cardRateRules)) ?>;

            function updateCardFields() {
                const selectedMachine = machineSelect.options[machineSelect.selectedIndex];
                const selectedBrand = brandSelect.options[brandSelect.selectedIndex];
                const selected = paymentType.options[paymentType.selectedIndex];
                if (!selected) return;

                const machineId = parseInt(selectedMachine?.dataset.id || '0', 10);
                const brandId = parseInt(selectedBrand?.dataset.id || '0', 10);
                const paymentId = parseInt(selected.dataset.id || '0', 10);
                const rule = rateRules.find((r) => r.machine_id === machineId && r.brand_id === brandId && r.payment_config_id === paymentId);

                const paymentName = (selected.value || '').toLowerCase();
                const defaultDays = paymentName.includes('debito') ? 1 : 30;
                const fallbackFee = parseFloat(selected.dataset.fee || '0');
                const fallbackDays = parseInt(selected.dataset.days || String(defaultDays), 10);
                const fee = rule ? rule.fee_percent : fallbackFee;
                const days = rule ? parseInt(rule.release_days, 10) : fallbackDays;
                feeField.value = fee;

                if (saleDateField.value && Number.isFinite(days)) {
                    const date = new Date(saleDateField.value + 'T00:00:00');
                    date.setDate(date.getDate() + days);
                    const yyyy = date.getFullYear();
                    const mm = String(date.getMonth() + 1).padStart(2, '0');
                    const dd = String(date.getDate()).padStart(2, '0');
                    releaseDateField.value = `${yyyy}-${mm}-${dd}`;
                }
            }

            machineSelect.addEventListener('change', updateCardFields);
            brandSelect.addEventListener('change', updateCardFields);
            paymentType.addEventListener('change', updateCardFields);
            saleDateField.addEventListener('change', updateCardFields);
        })();

        function openCardEditModal(card) {
            document.getElementById('card_edit_id').value = card.id;
            document.getElementById('card_edit_machine').value = card.machine;
            document.getElementById('card_edit_brand').value = card.brand;
            document.getElementById('card_edit_type').value = card.card_type;
            document.getElementById('card_edit_sale_location').value = card.sale_location || '';
            document.getElementById('card_edit_fee').value = card.fee_percent;
            document.getElementById('card_edit_gross').value = card.gross_value;
            document.getElementById('card_edit_sale').value = card.sale_date;
            document.getElementById('card_edit_release').value = card.expected_release_date;
            document.getElementById('card_edit_received').checked = Number(card.received) === 1;
            document.getElementById('cardEditModal').showModal();
        }

        function openCardSettleModal(id) {
            document.getElementById('card_settle_id').value = id;
            document.getElementById('cardSettleModal').showModal();
        }
    </script>
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
        <?php foreach ($checks as $c): ?><tr><td><?= $c['check_type'] ?></td><td><?= htmlspecialchars($c['customer']) ?></td><td><?= htmlspecialchars($c['bank']) ?></td><td><?= htmlspecialchars($c['check_number']) ?></td><td><?= dateBr((string) $c['due_date']) ?></td><td><?= money((float) $c['amount']) ?></td><td><?= $c['cleared'] ? 'Sim' : 'Não' ?></td><td><?= $c['compensated'] ? 'Sim' : 'Não' ?></td><td><?= $c['returned'] ? 'Sim' : 'Não' ?></td></tr><?php endforeach; ?>
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
        <?php foreach ($reconciliations as $r): ?><tr><td><?= htmlspecialchars($r['bank_name']) ?></td><td><?= dateBr((string) $r['movement_date']) ?></td><td><?= htmlspecialchars((string) $r['description']) ?></td><td><?= money((float) $r['system_amount']) ?></td><td><?= money((float) $r['bank_amount']) ?></td><td><?= $r['reconciled'] ? 'Conciliado' : 'Não conciliado' ?></td></tr><?php endforeach; ?>
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
        <?php foreach ($closings as $f): ?><tr><td><?= dateBr((string) $f['opening_date']) ?></td><td><?= money((float) $f['opening_amount']) ?></td><td><?= money((float) $f['total_entries']) ?></td><td><?= money((float) $f['total_exits']) ?></td><td><?= money((float) $f['counted_amount']) ?></td><td><?= money((float) $f['cash_difference']) ?></td><td><?= htmlspecialchars((string) $f['notes']) ?></td></tr><?php endforeach; ?>
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
    <button type="button" class="btn-danger" onclick="document.getElementById('resetSystemModal').showModal()">Resetar sistema</button>

    <dialog id="resetSystemModal">
        <form method="post">
            <input type="hidden" name="action" value="system_reset">
            <h4>Selecione os dados para excluir</h4>
            <label><input type="checkbox" name="reset_targets[]" value="transactions"> Fluxo de caixa / transações</label><br>
            <label><input type="checkbox" name="reset_targets[]" value="payables"> Contas a pagar</label><br>
            <label><input type="checkbox" name="reset_targets[]" value="cards"> Cartões</label><br>
            <label><input type="checkbox" name="reset_targets[]" value="checks"> Cheques</label><br>
            <label><input type="checkbox" name="reset_targets[]" value="reconciliation"> Conciliação bancária</label><br>
            <label><input type="checkbox" name="reset_targets[]" value="closing"> Fechamento de caixa</label><br>
            <label><input type="checkbox" name="reset_targets[]" value="suppliers"> Fornecedores</label><br>
            <label><input type="checkbox" name="reset_targets[]" value="settings"> Cadastros de configurações</label><br>
            <p class="small">Atenção: esta ação exclui permanentemente os registros selecionados.</p>
            <button class="btn-danger" onclick="return confirm('Confirma o reset dos dados selecionados?')">Excluir selecionados</button>
            <button type="button" onclick="document.getElementById('resetSystemModal').close()">Cancelar</button>
        </form>
    </dialog>

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

    <h4>Máquinas de cartão</h4>
    <form method="post">
        <input type="hidden" name="action" value="card_machine_add">
        <input name="name" placeholder="Nova máquina" required>
        <button>Adicionar máquina</button>
    </form>
    <table>
        <tr><th>Máquina</th><th>Editar</th><th>Excluir</th></tr>
        <?php foreach ($cardMachines as $machine): ?>
            <tr>
                <td><?= htmlspecialchars($machine['name']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="card_machine_update">
                        <input type="hidden" name="id" value="<?= $machine['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars($machine['name']) ?>" required>
                        <button>Salvar</button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Excluir máquina?')">
                        <input type="hidden" name="action" value="card_machine_delete">
                        <input type="hidden" name="id" value="<?= $machine['id'] ?>">
                        <button>Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Bandeiras de cartão</h4>
    <form method="post">
        <input type="hidden" name="action" value="card_brand_add">
        <input name="name" placeholder="Nova bandeira" required>
        <button>Adicionar bandeira</button>
    </form>
    <table>
        <tr><th>Bandeira</th><th>Editar</th><th>Excluir</th></tr>
        <?php foreach ($cardBrands as $brand): ?>
            <tr>
                <td><?= htmlspecialchars($brand['name']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="card_brand_update">
                        <input type="hidden" name="id" value="<?= $brand['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars($brand['name']) ?>" required>
                        <button>Salvar</button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Excluir bandeira?')">
                        <input type="hidden" name="action" value="card_brand_delete">
                        <input type="hidden" name="id" value="<?= $brand['id'] ?>">
                        <button>Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Locais de venda</h4>
    <form method="post">
        <input type="hidden" name="action" value="sale_location_add">
        <input name="name" placeholder="Novo local" required>
        <button>Adicionar local</button>
    </form>
    <table>
        <tr><th>Local</th><th>Editar</th><th>Excluir</th></tr>
        <?php foreach ($saleLocations as $location): ?>
            <tr>
                <td><?= htmlspecialchars($location['name']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="sale_location_update">
                        <input type="hidden" name="id" value="<?= $location['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars($location['name']) ?>" required>
                        <button>Salvar</button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Excluir local?')">
                        <input type="hidden" name="action" value="sale_location_delete">
                        <input type="hidden" name="id" value="<?= $location['id'] ?>">
                        <button>Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Caixas (Frente de Caixa)</h4>
    <form method="post">
        <input type="hidden" name="action" value="front_cash_register_add">
        <input name="name" placeholder="Novo caixa" required>
        <button>Adicionar caixa</button>
    </form>
    <table>
        <tr><th>Caixa</th><th>Editar</th><th>Excluir</th></tr>
        <?php foreach ($frontCashRegisters as $register): ?>
            <tr>
                <td><?= htmlspecialchars((string) $register['name']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="front_cash_register_update">
                        <input type="hidden" name="id" value="<?= (int) $register['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars((string) $register['name']) ?>" required>
                        <button>Salvar</button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Excluir caixa?')">
                        <input type="hidden" name="action" value="front_cash_register_delete">
                        <input type="hidden" name="id" value="<?= (int) $register['id'] ?>">
                        <button>Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Vendedores (Vendas de Gás)</h4>
    <form method="post">
        <input type="hidden" name="action" value="front_cash_vendor_add">
        <input name="name" placeholder="Novo vendedor" required>
        <button>Adicionar vendedor</button>
    </form>
    <table>
        <tr><th>Vendedor</th><th>Editar</th><th>Excluir</th></tr>
        <?php foreach ($frontCashVendors as $vendor): ?>
            <tr>
                <td><?= htmlspecialchars((string) $vendor['name']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="front_cash_vendor_update">
                        <input type="hidden" name="id" value="<?= (int) $vendor['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars((string) $vendor['name']) ?>" required>
                        <button>Salvar</button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Excluir vendedor?')">
                        <input type="hidden" name="action" value="front_cash_vendor_delete">
                        <input type="hidden" name="id" value="<?= (int) $vendor['id'] ?>">
                        <button>Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Formas de pagamento de cartão</h4>
    <form method="post">
        <input type="hidden" name="action" value="card_payment_config_add">
        <input name="name" placeholder="Forma (ex: debito)" required>
        <button>Adicionar forma cartão</button>
    </form>
    <table>
        <tr><th>Forma</th><th>Salvar</th><th>Excluir</th></tr>
        <?php foreach ($cardPaymentConfigs as $config): ?>
            <tr>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="card_payment_config_update">
                        <input type="hidden" name="id" value="<?= $config['id'] ?>">
                        <input name="name" value="<?= htmlspecialchars($config['name']) ?>" required>
                </td>
                <td><button>Salvar</button></form></td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Excluir forma de cartão?')">
                        <input type="hidden" name="action" value="card_payment_config_delete">
                        <input type="hidden" name="id" value="<?= $config['id'] ?>">
                        <button>Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>Taxas específicas por máquina + bandeira + forma</h4>
    <form method="post">
        <input type="hidden" name="action" value="card_rate_rule_add">
        <select name="machine_id" required>
            <option value="">Máquina</option>
            <?php foreach ($cardMachines as $machine): ?>
                <option value="<?= $machine['id'] ?>"><?= htmlspecialchars($machine['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="brand_id" required>
            <option value="">Bandeira</option>
            <?php foreach ($cardBrands as $brand): ?>
                <option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="payment_config_id" required>
            <option value="">Forma</option>
            <?php foreach ($cardPaymentConfigs as $config): ?>
                <option value="<?= $config['id'] ?>"><?= htmlspecialchars($config['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="fee_percent" type="number" step="0.01" placeholder="Taxa %" required>
        <input name="release_days" type="number" step="1" placeholder="Dias vencimento" required>
        <button>Adicionar regra</button>
    </form>
    <table>
        <tr><th>Máquina</th><th>Bandeira</th><th>Forma</th><th>Taxa %</th><th>Dias</th><th>Salvar</th><th>Excluir</th></tr>
        <?php foreach ($cardRateRules as $rule): ?>
            <tr>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="card_rate_rule_update">
                        <input type="hidden" name="id" value="<?= $rule['id'] ?>">
                        <select name="machine_id" required>
                            <?php foreach ($cardMachines as $machine): ?>
                                <option value="<?= $machine['id'] ?>" <?= (int) $rule['machine_id'] === (int) $machine['id'] ? 'selected' : '' ?>><?= htmlspecialchars($machine['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                </td>
                <td>
                        <select name="brand_id" required>
                            <?php foreach ($cardBrands as $brand): ?>
                                <option value="<?= $brand['id'] ?>" <?= (int) $rule['brand_id'] === (int) $brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars($brand['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                </td>
                <td>
                        <select name="payment_config_id" required>
                            <?php foreach ($cardPaymentConfigs as $config): ?>
                                <option value="<?= $config['id'] ?>" <?= (int) $rule['payment_config_id'] === (int) $config['id'] ? 'selected' : '' ?>><?= htmlspecialchars($config['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                </td>
                <td><input name="fee_percent" type="number" step="0.01" value="<?= $rule['fee_percent'] ?>" required></td>
                <td><input name="release_days" type="number" step="1" value="<?= $rule['release_days'] ?>" required></td>
                <td><button>Salvar</button></form></td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Excluir regra de taxa?')">
                        <input type="hidden" name="action" value="card_rate_rule_delete">
                        <input type="hidden" name="id" value="<?= $rule['id'] ?>">
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
