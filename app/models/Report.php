<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Report extends Model
{
    public function salesByPeriod(string $start, string $end): array
    {
        $stmt = $this->db->prepare('SELECT DATE(created_at) day, COUNT(*) orders, SUM(total_amount) total FROM orders WHERE DATE(created_at) BETWEEN :s AND :e AND status<>"cancelado" GROUP BY DATE(created_at) ORDER BY day');
        $stmt->execute(['s' => $start, 'e' => $end]);
        return $stmt->fetchAll();
    }

    public function salesByProduct(string $start, string $end): array
    {
        $stmt = $this->db->prepare('SELECT oi.product_name, SUM(oi.quantity) qty, SUM(oi.total_price) total FROM order_items oi INNER JOIN orders o ON o.id=oi.order_id WHERE DATE(o.created_at) BETWEEN :s AND :e AND o.status<>"cancelado" GROUP BY oi.product_name ORDER BY total DESC');
        $stmt->execute(['s' => $start, 'e' => $end]);
        return $stmt->fetchAll();
    }

    public function salesByCategory(string $start, string $end): array
    {
        $categoryTable = $this->hasTable('categories') ? 'categories' : 'product_categories';

        $stmt = $this->db->prepare("SELECT c.name category, SUM(oi.total_price) total
                                    FROM order_items oi
                                    INNER JOIN products p ON p.id = oi.product_id
                                    INNER JOIN {$categoryTable} c ON c.id = p.category_id
                                    INNER JOIN orders o ON o.id = oi.order_id
                                    WHERE DATE(o.created_at) BETWEEN :s AND :e
                                      AND o.status <> 'cancelado'
                                    GROUP BY c.name
                                    ORDER BY total DESC");
        $stmt->execute(['s' => $start, 'e' => $end]);
        return $stmt->fetchAll();
    }

    public function salesByPayment(string $start, string $end): array
    {
        $stmt = $this->db->prepare('SELECT payment_method, SUM(total_amount) total FROM orders WHERE DATE(created_at) BETWEEN :s AND :e AND status<>"cancelado" GROUP BY payment_method');
        $stmt->execute(['s' => $start, 'e' => $end]);
        return $stmt->fetchAll();
    }

    public function lowStock(): array
    {
        return $this->db->query('SELECT * FROM stock_items WHERE current_stock <= min_stock ORDER BY current_stock ASC')->fetchAll();
    }

    public function receivablesOpen(): array
    {
        return $this->db->query("SELECT ar.*, c.name customer_name FROM accounts_receivable ar INNER JOIN customers c ON c.id=ar.customer_id WHERE ar.status IN ('pendente','parcial') ORDER BY ar.due_date")->fetchAll();
    }

    public function dre(string $start, string $end): array
    {
        $salesStmt = $this->db->prepare('SELECT COALESCE(SUM(subtotal),0) AS gross, COALESCE(SUM(discount_amount),0) AS discounts, COALESCE(SUM(total_amount),0) AS net FROM orders WHERE DATE(created_at) BETWEEN :s AND :e AND status<>"cancelado"');
        $salesStmt->execute(['s' => $start, 'e' => $end]);
        $sales = $salesStmt->fetch();

        $cmvExpr = $this->cmvExpression();
        $cmvStmt = $this->db->prepare("SELECT COALESCE(SUM({$cmvExpr} * oi.quantity),0)
                                      FROM order_items oi
                                      INNER JOIN orders o ON o.id=oi.order_id
                                      WHERE DATE(o.created_at) BETWEEN :s AND :e
                                        AND o.status<>'cancelado'");
        $cmvStmt->execute(['s' => $start, 'e' => $end]);
        $cmv = (float)$cmvStmt->fetchColumn();

        $typeColumn = $this->firstExistingColumn('financial_entries', ['type', 'entry_type']);
        $statusColumn = $this->firstExistingColumn('financial_entries', ['status']);
        $dateColumn = $this->firstExistingColumn('financial_entries', ['entry_date', 'date', 'created_at']) ?? 'created_at';

        if ($typeColumn) {
            $dateExpr = $dateColumn === 'created_at' ? 'DATE(created_at)' : $dateColumn;
            $whereStatus = $statusColumn ? "AND {$statusColumn} = 'realizado'" : '';
            $despStmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0)
                                            FROM financial_entries
                                            WHERE {$typeColumn} IN ('saida','despesa')
                                              AND {$dateExpr} BETWEEN :s AND :e
                                              {$whereStatus}");
            $despStmt->execute(['s' => $start, 'e' => $end]);
            $despesas = (float)$despStmt->fetchColumn();
        } else {
            $despesas = 0.0;
        }

        $gross = (float)($sales['gross'] ?? 0);
        $discounts = (float)($sales['discounts'] ?? 0);
        $net = (float)($sales['net'] ?? 0);
        $lucroBruto = $net - $cmv;
        $lucroLiquido = $lucroBruto - $despesas;

        return compact('gross','discounts','net','cmv','lucroBruto','despesas','lucroLiquido');
    }

    public function conciliation(string $start, string $end): array
    {
        $ordersTotalStmt = $this->db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) BETWEEN :s AND :e AND status <> 'cancelado'");
        $ordersTotalStmt->execute(['s' => $start, 'e' => $end]);
        $ordersTotal = (float)$ordersTotalStmt->fetchColumn();

        $cancelledStmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN :s AND :e AND status = 'cancelado'");
        $cancelledStmt->execute(['s' => $start, 'e' => $end]);
        $cancelledOrders = (int)$cancelledStmt->fetchColumn();

        $cashDateCol = $this->firstExistingColumn('cash_movements', ['created_at', 'movement_date']) ?? 'created_at';
        $cashDateExpr = $cashDateCol === 'created_at' ? 'DATE(created_at)' : $cashDateCol;
        $cashTypeCol = $this->firstExistingColumn('cash_movements', ['type', 'movement_type']);
        $cashTypeWhere = $cashTypeCol ? "AND {$cashTypeCol} = 'venda'" : '';
        $cashStmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM cash_movements WHERE {$cashDateExpr} BETWEEN :s AND :e {$cashTypeWhere}");
        $cashStmt->execute(['s' => $start, 'e' => $end]);
        $cashSales = (float)$cashStmt->fetchColumn();

        $entryTypeCol = $this->firstExistingColumn('financial_entries', ['type', 'entry_type']);
        $entryDateCol = $this->firstExistingColumn('financial_entries', ['entry_date', 'date', 'created_at']) ?? 'created_at';
        $entryDateExpr = $entryDateCol === 'created_at' ? 'DATE(created_at)' : $entryDateCol;
        $finEntryIn = 0.0;
        $finEntryOut = 0.0;
        if ($entryTypeCol !== null) {
            $inStmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM financial_entries WHERE {$entryDateExpr} BETWEEN :s AND :e AND {$entryTypeCol} IN ('entrada','recebimento')");
            $inStmt->execute(['s' => $start, 'e' => $end]);
            $finEntryIn = (float)$inStmt->fetchColumn();

            $outStmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM financial_entries WHERE {$entryDateExpr} BETWEEN :s AND :e AND {$entryTypeCol} IN ('saida','despesa')");
            $outStmt->execute(['s' => $start, 'e' => $end]);
            $finEntryOut = (float)$outStmt->fetchColumn();
        }

        $receivablesOpen = 0.0;
        if ($this->hasTable('accounts_receivable')) {
            $receivableStmt = $this->db->query("SELECT COALESCE(SUM(total_amount - paid_amount),0) FROM accounts_receivable WHERE status IN ('pendente','parcial')");
            $receivablesOpen = (float)$receivableStmt->fetchColumn();
        }

        $orderPayStmt = $this->db->prepare("SELECT payment_method, COALESCE(SUM(total_amount),0) total FROM orders WHERE DATE(created_at) BETWEEN :s AND :e AND status <> 'cancelado' GROUP BY payment_method");
        $orderPayStmt->execute(['s' => $start, 'e' => $end]);
        $orderByPayment = [];
        foreach ($orderPayStmt->fetchAll() as $row) {
            $orderByPayment[$row['payment_method']] = (float)$row['total'];
        }

        $cashByPayment = [];
        $cashPayStmt = $this->db->prepare("SELECT payment_method, COALESCE(SUM(amount),0) total FROM cash_movements WHERE {$cashDateExpr} BETWEEN :s AND :e {$cashTypeWhere} GROUP BY payment_method");
        $cashPayStmt->execute(['s' => $start, 'e' => $end]);
        foreach ($cashPayStmt->fetchAll() as $row) {
            $cashByPayment[$row['payment_method']] = (float)$row['total'];
        }

        $paymentRows = [];
        foreach (array_unique(array_merge(array_keys($orderByPayment), array_keys($cashByPayment))) as $payment) {
            $orderTotal = $orderByPayment[$payment] ?? 0;
            $cashTotal = $cashByPayment[$payment] ?? 0;
            $paymentRows[] = [
                'payment_method' => $payment,
                'order_total' => $orderTotal,
                'cash_total' => $cashTotal,
                'diff' => $cashTotal - $orderTotal,
            ];
        }

        return [
            'ordersTotal' => $ordersTotal,
            'cashSales' => $cashSales,
            'cashDiff' => $cashSales - $ordersTotal,
            'financialIn' => $finEntryIn,
            'financialOut' => $finEntryOut,
            'receivablesOpen' => $receivablesOpen,
            'cancelledOrders' => $cancelledOrders,
            'paymentRows' => $paymentRows,
        ];
    }

    private function cmvExpression(): string
    {
        if ($this->hasColumn('order_items', 'unit_cost')) {
            return 'oi.unit_cost';
        }

        if ($this->hasColumn('order_items', 'cost_price')) {
            return 'oi.cost_price';
        }

        if ($this->hasColumn('order_items', 'unit_price')) {
            return 'oi.unit_price';
        }

        return '0';
    }

    private function hasTable(string $table): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
        $stmt->execute(['t' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }
        return null;
    }
}
