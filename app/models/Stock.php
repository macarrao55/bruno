<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Stock extends Model
{
    public function items(): array
    {
        if ($this->hasColumn('stock_items', 'active')) {
            return $this->db->query('SELECT * FROM stock_items WHERE active = 1 ORDER BY name')->fetchAll();
        }

        return $this->db->query('SELECT * FROM stock_items ORDER BY name')->fetchAll();
    }

    public function createItem(array $data): bool
    {
        $columns = ['name', 'unit', 'current_stock', 'min_stock', 'average_cost'];
        $values = [':name', ':unit', ':current_stock', ':min_stock', ':average_cost'];

        if ($this->hasColumn('stock_items', 'active')) {
            $columns[] = 'active';
            $values[] = '1';
        }
        if ($this->hasColumn('stock_items', 'created_at')) {
            $columns[] = 'created_at';
            $values[] = 'NOW()';
        }
        if ($this->hasColumn('stock_items', 'updated_at')) {
            $columns[] = 'updated_at';
            $values[] = 'NOW()';
        }

        $sql = sprintf('INSERT INTO stock_items (%s) VALUES (%s)', implode(',', $columns), implode(',', $values));
        return $this->db->prepare($sql)->execute([
            'name' => $data['name'],
            'unit' => $data['unit'],
            'current_stock' => $data['current_stock'],
            'min_stock' => $data['min_stock'],
            'average_cost' => $data['average_cost'],
        ]);
    }

    public function updateItem(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $sets = [
            'name = :name',
            'unit = :unit',
            'current_stock = :current_stock',
            'min_stock = :min_stock',
            'average_cost = :average_cost',
        ];

        if ($this->hasColumn('stock_items', 'updated_at')) {
            $sets[] = 'updated_at = NOW()';
        }

        $sql = 'UPDATE stock_items SET ' . implode(', ', $sets) . ' WHERE id = :id';
        return $this->db->prepare($sql)->execute([
            'id' => $id,
            'name' => $data['name'],
            'unit' => $data['unit'],
            'current_stock' => $data['current_stock'],
            'min_stock' => $data['min_stock'],
            'average_cost' => $data['average_cost'],
        ]);
    }

    public function deleteItem(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        if ($this->hasColumn('stock_items', 'active')) {
            $sql = 'UPDATE stock_items SET active = 0' . ($this->hasColumn('stock_items', 'updated_at') ? ', updated_at = NOW()' : '') . ' WHERE id = :id';
            return $this->db->prepare($sql)->execute(['id' => $id]);
        }

        return $this->db->prepare('DELETE FROM stock_items WHERE id = :id')->execute(['id' => $id]);
    }

    public function products(): array
    {
        if ($this->hasColumn('products', 'active')) {
            return $this->db->query('SELECT id, name FROM products WHERE active = 1 ORDER BY name')->fetchAll();
        }

        return $this->db->query('SELECT id, name FROM products ORDER BY name')->fetchAll();
    }

    public function recipes(): array
    {
        $activeFilter = $this->hasColumn('product_recipes', 'active') ? ' WHERE pr.active = 1' : '';
        return $this->db->query("
            SELECT
                pr.id,
                pr.product_id,
                pr.stock_item_id,
                pr.quantity_used,
                p.name AS product_name,
                si.name AS stock_item_name,
                si.unit AS stock_item_unit
            FROM product_recipes pr
            INNER JOIN products p ON p.id = pr.product_id
            INNER JOIN stock_items si ON si.id = pr.stock_item_id
            {$activeFilter}
            ORDER BY p.name, si.name
        ")->fetchAll();
    }

    public function saveRecipe(int $productId, int $stockItemId, float $quantityUsed): bool
    {
        if ($productId <= 0 || $stockItemId <= 0 || $quantityUsed <= 0) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $existing = $this->db->prepare('SELECT id FROM product_recipes WHERE product_id = :product_id AND stock_item_id = :stock_item_id LIMIT 1');
            $existing->execute(['product_id' => $productId, 'stock_item_id' => $stockItemId]);
            $row = $existing->fetch();

            if ($row) {
                $sets = ['quantity_used = :quantity_used'];
                if ($this->hasColumn('product_recipes', 'active')) {
                    $sets[] = 'active = 1';
                }
                if ($this->hasColumn('product_recipes', 'updated_at')) {
                    $sets[] = 'updated_at = NOW()';
                }

                $sql = 'UPDATE product_recipes SET ' . implode(', ', $sets) . ' WHERE id = :id';
                $ok = $this->db->prepare($sql)->execute(['id' => $row['id'], 'quantity_used' => $quantityUsed]);
            } else {
                $columns = ['product_id', 'stock_item_id', 'quantity_used'];
                $values = [':product_id', ':stock_item_id', ':quantity_used'];

                if ($this->hasColumn('product_recipes', 'active')) {
                    $columns[] = 'active';
                    $values[] = '1';
                }
                if ($this->hasColumn('product_recipes', 'created_at')) {
                    $columns[] = 'created_at';
                    $values[] = 'NOW()';
                }
                if ($this->hasColumn('product_recipes', 'updated_at')) {
                    $columns[] = 'updated_at';
                    $values[] = 'NOW()';
                }

                $sql = sprintf('INSERT INTO product_recipes (%s) VALUES (%s)', implode(',', $columns), implode(',', $values));
                $ok = $this->db->prepare($sql)->execute([
                    'product_id' => $productId,
                    'stock_item_id' => $stockItemId,
                    'quantity_used' => $quantityUsed,
                ]);
            }

            if (!$ok) {
                $this->db->rollBack();
                return false;
            }

            $controlsCol = $this->hasColumn('products', 'controls_stock') ? 'controls_stock' : ($this->hasColumn('products', 'stock_control') ? 'stock_control' : null);
            if ($controlsCol !== null) {
                $productUpdate = "UPDATE products SET {$controlsCol} = 1";
                if ($this->hasColumn('products', 'updated_at')) {
                    $productUpdate .= ', updated_at = NOW()';
                }
                $productUpdate .= ' WHERE id = :id';
                $this->db->prepare($productUpdate)->execute(['id' => $productId]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteRecipe(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        if ($this->hasColumn('product_recipes', 'active')) {
            $sql = 'UPDATE product_recipes SET active = 0' . ($this->hasColumn('product_recipes', 'updated_at') ? ', updated_at = NOW()' : '') . ' WHERE id = :id';
            return $this->db->prepare($sql)->execute(['id' => $id]);
        }

        return $this->db->prepare('DELETE FROM product_recipes WHERE id = :id')->execute(['id' => $id]);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column');
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
