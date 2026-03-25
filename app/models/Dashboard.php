<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Dashboard extends Model
{
    private static bool $lifecycleColumnsEnsured = false;

    public function stats(): array
    {
        $this->ensureLifecycleColumns();

        $today = (float)$this->db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status<>'cancelado'")->fetchColumn();
        $orders = (int)$this->db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE() AND status<>'cancelado'")->fetchColumn();
        $ticket = $orders > 0 ? $today / $orders : 0;
        $receivable = (float)$this->db->query("SELECT COALESCE(SUM(total_amount-paid_amount),0) FROM accounts_receivable WHERE status IN ('pendente','parcial')")->fetchColumn();
        $cashOpen = (int)$this->db->query("SELECT COUNT(*) FROM cash_registers WHERE status='aberto'")->fetchColumn();

        $pay = $this->db->query("SELECT payment_method, SUM(total_amount) total FROM orders WHERE DATE(created_at)=CURDATE() AND status<>'cancelado' GROUP BY payment_method")->fetchAll();
        $topProducts = $this->db->query("SELECT oi.product_name, SUM(oi.quantity) qty FROM order_items oi INNER JOIN orders o ON o.id=oi.order_id WHERE DATE(o.created_at)>=DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND o.status<>'cancelado' GROUP BY oi.product_name ORDER BY qty DESC LIMIT 5")->fetchAll();
        $topBairro = $this->topNeighborhoods();
        $newCustomers = (int)$this->db->query("SELECT COUNT(*) FROM customers WHERE DATE(created_at)=CURDATE()")->fetchColumn();

        $timeAverages = $this->orderTimeAverages();

        return compact('today', 'orders', 'ticket', 'receivable', 'cashOpen', 'pay', 'topProducts', 'topBairro', 'newCustomers', 'timeAverages');
    }

    private function orderTimeAverages(): array
    {
        if (!$this->hasColumn('orders', 'created_at')) {
            return [
                'day' => ['prep' => null, 'delivery' => null],
                'month' => ['prep' => null, 'delivery' => null],
                'year' => ['prep' => null, 'delivery' => null],
            ];
        }

        $prepTarget = $this->hasColumn('orders', 'prep_started_at')
            ? 'prep_started_at'
            : ($this->hasColumn('orders', 'ready_at') ? 'ready_at' : null);
        $deliveryTarget = $this->hasColumn('orders', 'delivered_at') ? 'delivered_at' : null;

        $makeQuery = function (string $periodCondition) use ($prepTarget, $deliveryTarget): string {
            $prepExpr = $prepTarget !== null
                ? "AVG(CASE WHEN {$prepTarget} IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, {$prepTarget}) END)"
                : 'NULL';
            $deliveryExpr = $deliveryTarget !== null
                ? "AVG(CASE WHEN {$deliveryTarget} IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, {$deliveryTarget}) END)"
                : 'NULL';

            return "SELECT {$prepExpr} AS avg_prep, {$deliveryExpr} AS avg_delivery
                    FROM orders
                    WHERE status <> 'cancelado' AND {$periodCondition}";
        };

        $day = $this->db->query($makeQuery('DATE(created_at) = CURDATE()'))->fetch();
        $month = $this->db->query($makeQuery('YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())'))->fetch();
        $year = $this->db->query($makeQuery('YEAR(created_at) = YEAR(CURDATE())'))->fetch();

        return [
            'day' => [
                'prep' => $day['avg_prep'] !== null ? (float)$day['avg_prep'] : null,
                'delivery' => $day['avg_delivery'] !== null ? (float)$day['avg_delivery'] : null,
            ],
            'month' => [
                'prep' => $month['avg_prep'] !== null ? (float)$month['avg_prep'] : null,
                'delivery' => $month['avg_delivery'] !== null ? (float)$month['avg_delivery'] : null,
            ],
            'year' => [
                'prep' => $year['avg_prep'] !== null ? (float)$year['avg_prep'] : null,
                'delivery' => $year['avg_delivery'] !== null ? (float)$year['avg_delivery'] : null,
            ],
        ];
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

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function ensureLifecycleColumns(): void
    {
        if (self::$lifecycleColumnsEnsured) {
            return;
        }

        $columns = ['prep_started_at', 'ready_at', 'delivered_at'];
        foreach ($columns as $column) {
            if ($this->hasColumn('orders', $column)) {
                continue;
            }

            try {
                $this->db->exec("ALTER TABLE orders ADD COLUMN {$column} DATETIME NULL");
            } catch (\Throwable) {
                // Mantém compatibilidade com ambientes sem permissão de alteração.
            }
        }

        self::$lifecycleColumnsEnsured = true;
    }
}
