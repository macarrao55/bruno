<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    public function all(): array
    {
        $categoryTable = $this->hasTable('categories') ? 'categories' : 'product_categories';
        $priceCol = $this->hasColumn('products', 'price') ? 'price' : 'sale_price';
        $costCol = $this->hasColumn('products', 'cost') ? 'cost' : 'cost_price';
        $controlsCol = $this->hasColumn('products', 'controls_stock') ? 'controls_stock' : 'stock_control';
        $addonsCol = $this->hasColumn('products', 'allows_addons') ? 'allows_addons' : 'allow_addons';

        $activeFilter = $this->hasColumn('products', 'active') ? 'p.active=1' : '1=1';
        $sql = "SELECT p.*, c.name category_name,
                       p.{$priceCol} AS price,
                       p.{$costCol} AS cost,
                       p.{$controlsCol} AS controls_stock,
                       p.{$addonsCol} AS allows_addons
                FROM products p
                INNER JOIN {$categoryTable} c ON c.id = p.category_id
                WHERE {$activeFilter}
                ORDER BY p.name";

        return $this->db->query($sql)->fetchAll();
    }

    public function categories(): array
    {
        $table = $this->hasTable('categories') ? 'categories' : 'product_categories';
        $activeFilter = $this->hasColumn($table, 'active') ? 'active=1' : '1=1';
        return $this->db->query("SELECT id,name FROM {$table} WHERE {$activeFilter} ORDER BY name")->fetchAll();
    }

    public function addonsByProduct(int $productId): array
    {
        // Schema novo: addons + product_addons (vínculo)
        if ($this->hasTable('addons')) {
            $sql = 'SELECT a.id, a.name, a.price
                    FROM product_addons pa
                    INNER JOIN addons a ON a.id = pa.addon_id
                    WHERE pa.product_id = :pid';
            if ($this->hasColumn('product_addons', 'active')) {
                $sql .= ' AND pa.active = 1';
            }
            if ($this->hasColumn('addons', 'active')) {
                $sql .= ' AND a.active = 1';
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['pid' => $productId]);
            return $stmt->fetchAll();
        }

        // Schema legado: product_addons (itens) + product_addon_links
        if ($this->hasTable('product_addon_links')) {
            $sql = 'SELECT a.id, a.name, a.price
                    FROM product_addon_links l
                    INNER JOIN product_addons a ON a.group_id = l.addon_group_id
                    WHERE l.product_id = :pid';
            if ($this->hasColumn('product_addons', 'active')) {
                $sql .= ' AND a.active = 1';
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['pid' => $productId]);
            return $stmt->fetchAll();
        }

        return [];
    }

    public function create(array $d): bool
    {
        $priceCol = $this->hasColumn('products', 'price') ? 'price' : 'sale_price';
        $costCol = $this->hasColumn('products', 'cost') ? 'cost' : 'cost_price';
        $controlsCol = $this->hasColumn('products', 'controls_stock') ? 'controls_stock' : 'stock_control';
        $addonsCol = $this->hasColumn('products', 'allows_addons') ? 'allows_addons' : 'allow_addons';

        $stmt = $this->db->prepare("INSERT INTO products (category_id,name,description,{$priceCol},{$costCol},{$controlsCol},{$addonsCol},active,created_at,updated_at)
                                    VALUES (:category_id,:name,:description,:price,:cost,:controls_stock,:allows_addons,:active,NOW(),NOW())");

        return $stmt->execute($d);
    }

    private function hasTable(string $table): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
        $stmt->execute(['t' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
