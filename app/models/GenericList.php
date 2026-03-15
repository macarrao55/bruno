<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class GenericList extends Model
{
    public function financialEntries(): array
    {
        return $this->db->query('SELECT * FROM financial_entries ORDER BY entry_date DESC LIMIT 100')->fetchAll();
    }

    public function cashMovements(): array
    {
        return $this->db->query('SELECT * FROM cash_movements ORDER BY created_at DESC LIMIT 100')->fetchAll();
    }

    public function employees(): array
    {
        return $this->db->query('SELECT * FROM employees WHERE deleted_at IS NULL ORDER BY name')->fetchAll();
    }

    public function stockItems(): array
    {
        return $this->db->query('SELECT * FROM stock_items WHERE deleted_at IS NULL ORDER BY name')->fetchAll();
    }

    public function loyalty(): array
    {
        return $this->db->query('SELECT c.name, lp.points_balance, lp.points_earned, lp.points_used FROM loyalty_points lp INNER JOIN customers c ON c.id=lp.customer_id ORDER BY lp.points_balance DESC')->fetchAll();
    }

    public function campaigns(): array
    {
        return $this->db->query('SELECT * FROM marketing_campaigns ORDER BY created_at DESC')->fetchAll();
    }

    public function auditLogs(): array
    {
        return $this->db->query('SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 200')->fetchAll();
    }
}
