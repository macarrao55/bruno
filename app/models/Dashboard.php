<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Dashboard extends Model
{
    public function stats(): array
    {
        $today = (float)$this->db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status<>'cancelado'")->fetchColumn();
        $orders = (int)$this->db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE() AND status<>'cancelado'")->fetchColumn();
        $ticket = $orders > 0 ? $today / $orders : 0;
        $receivable = (float)$this->db->query("SELECT COALESCE(SUM(total_amount-paid_amount),0) FROM accounts_receivable WHERE status IN ('pendente','parcial')")->fetchColumn();
        $cashOpen = (int)$this->db->query("SELECT COUNT(*) FROM cash_registers WHERE status='aberto'")->fetchColumn();

        $pay = $this->db->query("SELECT payment_method, SUM(total_amount) total FROM orders WHERE DATE(created_at)=CURDATE() AND status<>'cancelado' GROUP BY payment_method")->fetchAll();
        $topProducts = $this->db->query("SELECT oi.product_name, SUM(oi.quantity) qty FROM order_items oi INNER JOIN orders o ON o.id=oi.order_id WHERE DATE(o.created_at)>=DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND o.status<>'cancelado' GROUP BY oi.product_name ORDER BY qty DESC LIMIT 5")->fetchAll();
        $topBairro = $this->topNeighborhoods();
        $newCustomers = (int)$this->db->query("SELECT COUNT(*) FROM customers WHERE DATE(created_at)=CURDATE()")->fetchColumn();

        return compact('today', 'orders', 'ticket', 'receivable', 'cashOpen', 'pay', 'topProducts', 'topBairro', 'newCustomers');
    }

    private function topNeighborhoods(): array
    {
        $column = $this->resolveCustomersNeighborhoodColumn();
        if ($column === null) {
            return [];
        }

        $sql = "SELECT c.{$column} AS neighborhood, COUNT(*) qty
                FROM orders o
                INNER JOIN customers c ON c.id = o.customer_id
                WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  AND c.{$column} IS NOT NULL
                  AND c.{$column} <> ''
                GROUP BY c.{$column}
                ORDER BY qty DESC
                LIMIT 5";

        return $this->db->query($sql)->fetchAll();
    }

    private function resolveCustomersNeighborhoodColumn(): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT column_name
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = 'customers'
               AND column_name IN ('neighborhood', 'bairro')"
        );
        $stmt->execute();
        $cols = $stmt->fetchAll();
        $available = array_column($cols, 'column_name');

        if (in_array('neighborhood', $available, true)) {
            return 'neighborhood';
        }

        if (in_array('bairro', $available, true)) {
            return 'bairro';
        }

        return null;
    }
}
