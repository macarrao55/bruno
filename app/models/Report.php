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

        $cmvStmt = $this->db->prepare('SELECT COALESCE(SUM(unit_cost * quantity),0) FROM order_items oi INNER JOIN orders o ON o.id=oi.order_id WHERE DATE(o.created_at) BETWEEN :s AND :e AND o.status<>"cancelado"');
        $cmvStmt->execute(['s' => $start, 'e' => $end]);
        $cmv = (float)$cmvStmt->fetchColumn();

        $despStmt = $this->db->prepare('SELECT COALESCE(SUM(amount),0) FROM financial_entries WHERE type="saida" AND entry_date BETWEEN :s AND :e AND status="realizado"');
        $despStmt->execute(['s' => $start, 'e' => $end]);
        $despesas = (float)$despStmt->fetchColumn();

        $gross = (float)$sales['gross'];
        $discounts = (float)$sales['discounts'];
        $net = (float)$sales['net'];
        $lucroBruto = $net - $cmv;
        $lucroLiquido = $lucroBruto - $despesas;

        return compact('gross','discounts','net','cmv','lucroBruto','despesas','lucroLiquido');
    }

    private function hasTable(string $table): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
        $stmt->execute(['t' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
