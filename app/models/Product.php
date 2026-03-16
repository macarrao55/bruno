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

    public function addonGroups(): array
    {
        if (!$this->hasTable('addon_groups')) {
            return [];
        }

        $active = $this->hasColumn('addon_groups', 'active') ? 'WHERE active=1' : '';
        return $this->db->query("SELECT id,name FROM addon_groups {$active} ORDER BY name")->fetchAll();
    }

    public function allAddons(): array
    {
        if (!$this->hasTable('addons')) {
            return [];
        }

        $joins = $this->hasTable('addon_groups') ? ' LEFT JOIN addon_groups g ON g.id=a.addon_group_id ' : '';
        $groupName = $this->hasTable('addon_groups') ? 'g.name AS group_name,' : "'' AS group_name,";
        $where = $this->hasColumn('addons', 'active') ? 'WHERE a.active=1' : '';
        $sql = "SELECT a.id, a.name, a.price, {$groupName} a.addon_group_id
                FROM addons a
                {$joins}
                {$where}
                ORDER BY a.name";
        return $this->db->query($sql)->fetchAll();
    }

    public function createAddonGroup(string $name): bool
    {
        if (!$this->hasTable('addon_groups')) {
            return false;
        }

        $fields = ['name'];
        $values = [':name'];
        $params = ['name' => $name];

        if ($this->hasColumn('addon_groups', 'active')) {
            $fields[] = 'active';
            $values[] = '1';
        }
        if ($this->hasColumn('addon_groups', 'created_at')) {
            $fields[] = 'created_at';
            $values[] = 'NOW()';
        }
        if ($this->hasColumn('addon_groups', 'updated_at')) {
            $fields[] = 'updated_at';
            $values[] = 'NOW()';
        }

        $sql = sprintf('INSERT INTO addon_groups (%s) VALUES (%s)', implode(',', $fields), implode(',', $values));
        return $this->db->prepare($sql)->execute($params);
    }

    public function createAddon(int $groupId, string $name, float $price): bool
    {
        if (!$this->hasTable('addons')) {
            return false;
        }

        $fields = ['name'];
        $values = [':name'];
        $params = ['name' => $name, 'price' => $price, 'addon_group_id' => $groupId];

        if ($this->hasColumn('addons', 'addon_group_id')) {
            $fields[] = 'addon_group_id';
            $values[] = ':addon_group_id';
        } elseif ($this->hasColumn('addons', 'group_id')) {
            $fields[] = 'group_id';
            $values[] = ':addon_group_id';
        }

        $priceCol = $this->hasColumn('addons', 'price') ? 'price' : ($this->hasColumn('addons', 'value') ? 'value' : null);
        if ($priceCol === null) {
            return false;
        }

        $fields[] = $priceCol;
        $values[] = ':price';

        if ($this->hasColumn('addons', 'active')) {
            $fields[] = 'active';
            $values[] = '1';
        }
        if ($this->hasColumn('addons', 'created_at')) {
            $fields[] = 'created_at';
            $values[] = 'NOW()';
        }
        if ($this->hasColumn('addons', 'updated_at')) {
            $fields[] = 'updated_at';
            $values[] = 'NOW()';
        }

        $sql = sprintf('INSERT INTO addons (%s) VALUES (%s)', implode(',', $fields), implode(',', $values));
        return $this->db->prepare($sql)->execute($params);
    }

    public function attachAddonToProduct(int $productId, int $addonId): bool
    {
        if ($productId <= 0 || $addonId <= 0) {
            return false;
        }

        if ($this->hasTable('product_addons') && $this->hasColumn('product_addons', 'product_id') && $this->hasColumn('product_addons', 'addon_id')) {
            $fields = ['product_id', 'addon_id'];
            $values = [':product_id', ':addon_id'];

            if ($this->hasColumn('product_addons', 'active')) {
                $fields[] = 'active';
                $values[] = '1';
            }
            if ($this->hasColumn('product_addons', 'created_at')) {
                $fields[] = 'created_at';
                $values[] = 'NOW()';
            }
            if ($this->hasColumn('product_addons', 'updated_at')) {
                $fields[] = 'updated_at';
                $values[] = 'NOW()';
            }

            $sql = sprintf('INSERT IGNORE INTO product_addons (%s) VALUES (%s)', implode(',', $fields), implode(',', $values));
            return $this->db->prepare($sql)->execute(['product_id' => $productId, 'addon_id' => $addonId]);
        }

        if ($this->hasTable('product_addon_links')) {
            $fields = ['product_id', 'addon_group_id'];
            $values = [':product_id', '(SELECT group_id FROM product_addons WHERE id=:addon_id LIMIT 1)'];
            $sql = sprintf('INSERT IGNORE INTO product_addon_links (%s) VALUES (%s)', implode(',', $fields), implode(',', $values));
            return $this->db->prepare($sql)->execute(['product_id' => $productId, 'addon_id' => $addonId]);
        }

        return false;
    }

    public function addonsByProduct(int $productId): array
    {
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

        if ($this->hasTable('product_addon_links')) {
            $priceCol = $this->hasColumn('product_addons', 'price') ? 'price' : 'value';
            $sql = "SELECT a.id, a.name, a.{$priceCol} AS price
                    FROM product_addon_links l
                    INNER JOIN product_addons a ON a.group_id = l.addon_group_id
                    WHERE l.product_id = :pid";
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
