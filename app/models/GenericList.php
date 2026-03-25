<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class GenericList extends Model
{
    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table');
        $stmt->execute(['table' => $table]);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0) > 0;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column');
        $stmt->execute(['table' => $table, 'column' => $column]);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0) > 0;
    }

    public function financialEntries(): array
    {
        if (!$this->tableExists('financial_entries')) {
            return [];
        }

        return $this->db->query('SELECT * FROM financial_entries ORDER BY entry_date DESC LIMIT 100')->fetchAll();
    }

    public function cashMovements(): array
    {
        if (!$this->tableExists('cash_movements')) {
            return [];
        }

        return $this->db->query('SELECT * FROM cash_movements ORDER BY created_at DESC LIMIT 100')->fetchAll();
    }

    public function employees(): array
    {
        if ($this->tableExists('employees')) {
            if ($this->hasColumn('employees', 'deleted_at')) {
                return $this->db->query('SELECT * FROM employees WHERE deleted_at IS NULL ORDER BY name')->fetchAll();
            }

            return $this->db->query('SELECT * FROM employees ORDER BY name')->fetchAll();
        }

        if (!$this->tableExists('users') || !$this->tableExists('roles')) {
            return [];
        }

        $activeExpression = $this->hasColumn('users', 'active')
            ? "CASE WHEN u.active = 1 THEN 'ativo' ELSE 'inativo' END"
            : "'ativo'";

        return $this->db->query("
            SELECT
                u.name,
                r.name AS position,
                '' AS phone,
                u.created_at AS admission_date,
                {$activeExpression} AS status
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            ORDER BY u.name
        ")->fetchAll();
    }

    public function stockItems(): array
    {
        if (!$this->tableExists('stock_items')) {
            return [];
        }

        if ($this->hasColumn('stock_items', 'deleted_at')) {
            return $this->db->query('SELECT * FROM stock_items WHERE deleted_at IS NULL ORDER BY name')->fetchAll();
        }

        return $this->db->query('SELECT * FROM stock_items ORDER BY name')->fetchAll();
    }

    public function loyalty(): array
    {
        $customerPhoneExpr = $this->hasColumn('customers', 'phone') ? 'c.phone' : "''";
        $customerSpentExpr = $this->hasColumn('customers', 'total_spent') ? 'c.total_spent' : '0';
        $customerOrdersExpr = $this->hasColumn('customers', 'orders_count') ? 'c.orders_count' : '0';

        if ($this->tableExists('loyalty_points')) {
            return $this->db->query('
                SELECT
                    c.id AS customer_id,
                    c.name,
                    ' . $customerPhoneExpr . ' AS phone,
                    ' . $customerSpentExpr . ' AS total_spent,
                    ' . $customerOrdersExpr . ' AS orders_count,
                    lp.points_balance,
                    lp.points_earned,
                    lp.points_used,
                    NULL AS last_movement_at
                FROM loyalty_points lp
                INNER JOIN customers c ON c.id = lp.customer_id
                ORDER BY lp.points_balance DESC
            ')->fetchAll();
        }

        if (!$this->tableExists('loyalty_transactions')) {
            return [];
        }

        $ltTypeExpr = $this->hasColumn('loyalty_transactions', 'type')
            ? "CASE WHEN lt.type = 'credito' THEN lt.points ELSE -lt.points END"
            : 'lt.points';
        $ltCreditExpr = $this->hasColumn('loyalty_transactions', 'type')
            ? "CASE WHEN lt.type = 'credito' THEN lt.points ELSE 0 END"
            : 'lt.points';
        $ltDebitExpr = $this->hasColumn('loyalty_transactions', 'type')
            ? "CASE WHEN lt.type = 'debito' THEN lt.points ELSE 0 END"
            : '0';

        return $this->db->query("
            SELECT
                c.id AS customer_id,
                c.name,
                {$customerPhoneExpr} AS phone,
                {$customerSpentExpr} AS total_spent,
                {$customerOrdersExpr} AS orders_count,
                COALESCE(SUM({$ltTypeExpr}), 0) AS points_balance,
                COALESCE(SUM({$ltCreditExpr}), 0) AS points_earned,
                COALESCE(SUM({$ltDebitExpr}), 0) AS points_used,
                MAX(lt.created_at) AS last_movement_at
            FROM customers c
            LEFT JOIN loyalty_transactions lt ON lt.customer_id = c.id
            GROUP BY c.id, c.name
            ORDER BY points_balance DESC
        ")->fetchAll();
    }

    public function campaigns(): array
    {
        if (!$this->tableExists('marketing_campaigns')) {
            return [];
        }

        return $this->db->query('SELECT * FROM marketing_campaigns ORDER BY created_at DESC')->fetchAll();
    }

    public function auditLogs(): array
    {
        if (!$this->tableExists('audit_logs')) {
            return [];
        }

        return $this->db->query('SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 200')->fetchAll();
    }
}
