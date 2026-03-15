<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Dashboard extends Model
{
    public function stats(): array
    {
        $result = [];
        $result['sales_today'] = (float) ($this->db->query("SELECT COALESCE(SUM(total_amount),0) AS v FROM orders WHERE DATE(created_at)=CURDATE() AND status<>'cancelado'")->fetch()['v'] ?? 0);
        $result['sales_month'] = (float) ($this->db->query("SELECT COALESCE(SUM(total_amount),0) AS v FROM orders WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE()) AND status<>'cancelado'")->fetch()['v'] ?? 0);
        $result['orders_today'] = (int) ($this->db->query("SELECT COUNT(*) AS q FROM orders WHERE DATE(created_at)=CURDATE()") ->fetch()['q'] ?? 0);
        $result['avg_ticket'] = (float) ($this->db->query("SELECT COALESCE(AVG(total_amount),0) AS v FROM orders WHERE DATE(created_at)=CURDATE() AND status<>'cancelado'")->fetch()['v'] ?? 0);
        $result['cash_balance'] = (float) ($this->db->query("SELECT COALESCE(SUM(CASE WHEN type='entrada' THEN amount ELSE -amount END),0) AS v FROM cash_movements WHERE DATE(created_at)=CURDATE()") ->fetch()['v'] ?? 0);
        return $result;
    }

    public function chartSalesByDay(): array
    {
        return $this->db->query("SELECT DATE(created_at) AS day, SUM(total_amount) AS total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status<>'cancelado' GROUP BY DATE(created_at) ORDER BY day")->fetchAll();
    }
}
