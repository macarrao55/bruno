<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    public function all(): array
    {
        $sql = 'SELECT p.*, c.name AS category_name FROM products p INNER JOIN product_categories c ON c.id = p.category_id WHERE p.deleted_at IS NULL ORDER BY p.name';
        return $this->db->query($sql)->fetchAll();
    }

    public function categories(): array
    {
        return $this->db->query('SELECT id, name FROM product_categories WHERE active = 1 ORDER BY name')->fetchAll();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO products (category_id, code, name, description, sale_price, cost_price, margin_percent, active, stock_control, allow_addons, created_at, updated_at) VALUES (:category_id, :code, :name, :description, :sale_price, :cost_price, :margin_percent, :active, :stock_control, :allow_addons, NOW(), NOW())');
        return $stmt->execute($data);
    }
}
