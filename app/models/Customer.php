<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM customers WHERE deleted_at IS NULL ORDER BY name')->fetchAll();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO customers (name, phone_main, phone_secondary, birth_date, notes, active, created_at, updated_at) VALUES (:name, :phone_main, :phone_secondary, :birth_date, :notes, :active, NOW(), NOW())');
        return $stmt->execute($data);
    }
}
