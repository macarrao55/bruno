<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    public function all(): array
    {
        $sql = 'SELECT p.*, c.name category_name FROM products p INNER JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.name';
        return $this->db->query($sql)->fetchAll();
    }

    public function categories(): array
    {
        return $this->db->query('SELECT id,name FROM categories WHERE active=1 ORDER BY name')->fetchAll();
    }

    public function addonsByProduct(int $productId): array
    {
        $sql = 'SELECT a.id,a.name,a.price FROM product_addons pa INNER JOIN addons a ON a.id=pa.addon_id WHERE pa.product_id=:pid AND pa.active=1 AND a.active=1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pid' => $productId]);
        return $stmt->fetchAll();
    }

    public function create(array $d): bool
    {
        $stmt = $this->db->prepare('INSERT INTO products (category_id,name,description,price,cost,controls_stock,allows_addons,active,created_at,updated_at) VALUES (:category_id,:name,:description,:price,:cost,:controls_stock,:allows_addons,:active,NOW(),NOW())');
        return $stmt->execute($d);
    }
}
