<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM customers WHERE active=1 ORDER BY name')->fetchAll();
    }

    public function create(array $d): bool
    {
        $stmt = $this->db->prepare('INSERT INTO customers (name,phone,neighborhood,address,birth_date,notes,active,created_at,updated_at) VALUES (:name,:phone,:neighborhood,:address,:birth_date,:notes,1,NOW(),NOW())');
        return $stmt->execute($d);
    }

    public function updateStats(int $id, float $total): void
    {
        $stmt = $this->db->prepare('UPDATE customers SET total_spent=total_spent + :t, orders_count=orders_count+1, updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $id, 't' => $total]);
    }
}
