<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;

class Product extends Model
{
    public function all(array $filters = []): array
    {
        $categoryTable = $this->hasTable('categories') ? 'categories' : 'product_categories';
        $priceCol = $this->hasColumn('products', 'price') ? 'price' : 'sale_price';
        $costCol = $this->hasColumn('products', 'cost') ? 'cost' : 'cost_price';
        $controlsCol = $this->hasColumn('products', 'controls_stock') ? 'controls_stock' : 'stock_control';
        $addonsCol = $this->hasColumn('products', 'allows_addons') ? 'allows_addons' : 'allow_addons';

        $where = [];
        $params = [];

        if (($filters['name'] ?? '') !== '') {
            $where[] = 'p.name LIKE :name';
            $params['name'] = '%' . $filters['name'] . '%';
        }
        if (($filters['category_id'] ?? '') !== '') {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = (int)$filters['category_id'];
        }
        if (($filters['controls_stock'] ?? '') !== '') {
            $where[] = "p.{$controlsCol} = :controls_stock";
            $params['controls_stock'] = (int)$filters['controls_stock'];
        }
        if (($filters['allows_addons'] ?? '') !== '') {
            $where[] = "p.{$addonsCol} = :allows_addons";
            $params['allows_addons'] = (int)$filters['allows_addons'];
        }
        if (($filters['active'] ?? '') !== '' && $this->hasColumn('products', 'active')) {
            $where[] = 'p.active = :active';
            $params['active'] = (int)$filters['active'];
        } elseif ($this->hasColumn('products', 'active')) {
            $where[] = 'p.active = 1';
        }

        $whereSql = $where ? implode(' AND ', $where) : '1=1';
        $sql = "SELECT p.*, c.name category_name,
                       p.{$priceCol} AS price,
                       p.{$costCol} AS cost,
                       p.{$controlsCol} AS controls_stock,
                       p.{$addonsCol} AS allows_addons
                FROM products p
                INNER JOIN {$categoryTable} c ON c.id = p.category_id
                WHERE {$whereSql}
                ORDER BY p.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function categories(): array
    {
        $table = $this->hasTable('categories') ? 'categories' : 'product_categories';
        $activeFilter = $this->hasColumn($table, 'active') ? 'active=1' : '1=1';
        return $this->db->query("SELECT id,name FROM {$table} WHERE {$activeFilter} ORDER BY name")->fetchAll();
    }

    public function addonGroups(): array
    {
        $table = $this->hasTable('addon_groups') ? 'addon_groups' : ($this->hasTable('product_addon_groups') ? 'product_addon_groups' : null);
        if ($table === null) {
            return [];
        }

        $active = $this->hasColumn($table, 'active') ? 'WHERE active=1' : '';
        return $this->db->query("SELECT id,name FROM {$table} {$active} ORDER BY name")->fetchAll();
    }

    public function allAddons(): array
    {
        if ($this->hasTable('addons')) {
            $groupTable = $this->hasTable('addon_groups') ? 'addon_groups' : ($this->hasTable('product_addon_groups') ? 'product_addon_groups' : null);
            $groupColumn = $this->hasColumn('addons', 'addon_group_id') ? 'addon_group_id' : ($this->hasColumn('addons', 'group_id') ? 'group_id' : null);
            $priceCol = $this->hasColumn('addons', 'price') ? 'price' : ($this->hasColumn('addons', 'value') ? 'value' : 'price');

            $joins = ($groupTable && $groupColumn) ? " LEFT JOIN {$groupTable} g ON g.id = a.{$groupColumn} " : '';
            $groupName = ($groupTable && $groupColumn) ? 'g.name AS group_name,' : "'' AS group_name,";
            $groupSelect = $groupColumn ? "a.{$groupColumn} AS addon_group_id" : "NULL AS addon_group_id";
            $where = $this->hasColumn('addons', 'active') ? 'WHERE a.active=1' : '';

            $sql = "SELECT a.id, a.name, a.{$priceCol} AS price, {$groupName} {$groupSelect}
                    FROM addons a
                    {$joins}
                    {$where}
                    ORDER BY a.name";
            return $this->db->query($sql)->fetchAll();
        }

        // Schema legado: product_addons como tabela de adicionais (não pivô).
        if (!$this->hasTable('product_addons') || $this->hasColumn('product_addons', 'product_id')) {
            return [];
        }

        $groupTable = $this->hasTable('product_addon_groups') ? 'product_addon_groups' : ($this->hasTable('addon_groups') ? 'addon_groups' : null);
        $groupColumn = $this->hasColumn('product_addons', 'group_id') ? 'group_id' : ($this->hasColumn('product_addons', 'addon_group_id') ? 'addon_group_id' : null);
        $priceCol = $this->hasColumn('product_addons', 'price') ? 'price' : ($this->hasColumn('product_addons', 'value') ? 'value' : null);
        if ($priceCol === null) {
            return [];
        }

        $joins = ($groupTable && $groupColumn) ? " LEFT JOIN {$groupTable} g ON g.id = a.{$groupColumn} " : '';
        $groupName = ($groupTable && $groupColumn) ? 'g.name AS group_name,' : "'' AS group_name,";
        $groupSelect = $groupColumn ? "a.{$groupColumn} AS addon_group_id" : "NULL AS addon_group_id";
        $where = $this->hasColumn('product_addons', 'active') ? 'WHERE a.active=1' : '';
        $sql = "SELECT a.id, a.name, a.{$priceCol} AS price, {$groupName} {$groupSelect}
                FROM product_addons a
                {$joins}
                {$where}
                ORDER BY a.name";
        return $this->db->query($sql)->fetchAll();
    }

    public function createAddonGroup(string $name): bool
    {
        $table = $this->hasTable('addon_groups') ? 'addon_groups' : ($this->hasTable('product_addon_groups') ? 'product_addon_groups' : null);
        if ($table === null) {
            return false;
        }

        $fields = ['name'];
        $values = [':name'];
        $params = ['name' => $name];

        if ($this->hasColumn($table, 'active')) {
            $fields[] = 'active';
            $values[] = '1';
        }
        if ($this->hasColumn($table, 'created_at')) {
            $fields[] = 'created_at';
            $values[] = 'NOW()';
        }
        if ($this->hasColumn($table, 'updated_at')) {
            $fields[] = 'updated_at';
            $values[] = 'NOW()';
        }

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(',', $fields), implode(',', $values));
        return $this->db->prepare($sql)->execute($params);
    }

    public function createAddon(int $groupId, string $name, float $price): bool
    {
        $table = $this->hasTable('addons') ? 'addons' : (($this->hasTable('product_addons') && !$this->hasColumn('product_addons', 'product_id')) ? 'product_addons' : null);
        if ($table === null) {
            return false;
        }

        $fields = ['name'];
        $values = [':name'];
        $params = ['name' => $name, 'price' => $price, 'addon_group_id' => $groupId];

        if ($this->hasColumn($table, 'addon_group_id')) {
            $fields[] = 'addon_group_id';
            $values[] = ':addon_group_id';
        } elseif ($this->hasColumn($table, 'group_id')) {
            $fields[] = 'group_id';
            $values[] = ':addon_group_id';
        }

        $priceCol = $this->hasColumn($table, 'price') ? 'price' : ($this->hasColumn($table, 'value') ? 'value' : null);
        if ($priceCol === null) {
            return false;
        }

        $fields[] = $priceCol;
        $values[] = ':price';

        if ($this->hasColumn($table, 'active')) {
            $fields[] = 'active';
            $values[] = '1';
        }
        if ($this->hasColumn($table, 'created_at')) {
            $fields[] = 'created_at';
            $values[] = 'NOW()';
        }
        if ($this->hasColumn($table, 'updated_at')) {
            $fields[] = 'updated_at';
            $values[] = 'NOW()';
        }

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(',', $fields), implode(',', $values));
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
            if ($this->hasColumn('product_addon_links', 'addon_id')) {
                $sql = 'INSERT IGNORE INTO product_addon_links (product_id,addon_id) VALUES (:product_id,:addon_id)';
                return $this->db->prepare($sql)->execute(['product_id' => $productId, 'addon_id' => $addonId]);
            }

            if ($this->hasColumn('product_addon_links', 'addon_group_id')) {
                $addonGroupId = $this->resolveAddonGroupId($addonId);
                if ($addonGroupId <= 0) {
                    return false;
                }
                $sql = 'INSERT IGNORE INTO product_addon_links (product_id,addon_group_id) VALUES (:product_id,:addon_group_id)';
                return $this->db->prepare($sql)->execute(['product_id' => $productId, 'addon_group_id' => $addonGroupId]);
            }
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
            // links por addon_id
            if ($this->hasColumn('product_addon_links', 'addon_id')) {
                if ($this->hasTable('addons')) {
                    $priceCol = $this->hasColumn('addons', 'price') ? 'price' : ($this->hasColumn('addons', 'value') ? 'value' : 'price');
                    $sql = "SELECT a.id, a.name, a.{$priceCol} AS price
                            FROM product_addon_links l
                            INNER JOIN addons a ON a.id = l.addon_id
                            WHERE l.product_id = :pid";
                    if ($this->hasColumn('addons', 'active')) {
                        $sql .= ' AND a.active = 1';
                    }
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute(['pid' => $productId]);
                    return $stmt->fetchAll();
                }

                if ($this->hasTable('product_addons') && !$this->hasColumn('product_addons', 'product_id')) {
                    $priceCol = $this->hasColumn('product_addons', 'price') ? 'price' : 'value';
                    $sql = "SELECT a.id, a.name, a.{$priceCol} AS price
                            FROM product_addon_links l
                            INNER JOIN product_addons a ON a.id = l.addon_id
                            WHERE l.product_id = :pid";
                    if ($this->hasColumn('product_addons', 'active')) {
                        $sql .= ' AND a.active = 1';
                    }
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute(['pid' => $productId]);
                    return $stmt->fetchAll();
                }
            }

            // links por addon_group_id
            if ($this->hasColumn('product_addon_links', 'addon_group_id') && $this->hasTable('product_addons') && !$this->hasColumn('product_addons', 'product_id')) {
                $groupCol = $this->hasColumn('product_addons', 'group_id') ? 'group_id' : ($this->hasColumn('product_addons', 'addon_group_id') ? 'addon_group_id' : null);
                $priceCol = $this->hasColumn('product_addons', 'price') ? 'price' : ($this->hasColumn('product_addons', 'value') ? 'value' : null);
                if ($groupCol !== null && $priceCol !== null) {
                    $sql = "SELECT a.id, a.name, a.{$priceCol} AS price
                            FROM product_addon_links l
                            INNER JOIN product_addons a ON a.{$groupCol} = l.addon_group_id
                            WHERE l.product_id = :pid";
                    if ($this->hasColumn('product_addons', 'active')) {
                        $sql .= ' AND a.active = 1';
                    }
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute(['pid' => $productId]);
                    return $stmt->fetchAll();
                }
            }
        }

        return [];
    }


    public function update(int $id, array $d): bool
    {
        if ($id <= 0) {
            return false;
        }

        $priceCol = $this->hasColumn('products', 'price') ? 'price' : 'sale_price';
        $costCol = $this->hasColumn('products', 'cost') ? 'cost' : 'cost_price';
        $controlsCol = $this->hasColumn('products', 'controls_stock') ? 'controls_stock' : 'stock_control';
        $addonsCol = $this->hasColumn('products', 'allows_addons') ? 'allows_addons' : 'allow_addons';

        $sets = [
            'category_id=:category_id',
            'name=:name',
            'description=:description',
            "{$priceCol}=:price",
            "{$costCol}=:cost",
            "{$controlsCol}=:controls_stock",
            "{$addonsCol}=:allows_addons",
        ];

        if ($this->hasColumn('products', 'active')) {
            $sets[] = 'active=:active';
        }
        if ($this->hasColumn('products', 'code')) {
            $sets[] = 'code=:code';
        }
        if ($this->hasColumn('products', 'updated_at')) {
            $sets[] = 'updated_at=NOW()';
        }

        $sql = 'UPDATE products SET ' . implode(',', $sets) . ' WHERE id=:id';
        $params = [
            'id' => $id,
            'category_id' => (int)$d['category_id'],
            'name' => (string)$d['name'],
            'description' => (string)($d['description'] ?? ''),
            'price' => (float)$d['price'],
            'cost' => (float)$d['cost'],
            'controls_stock' => (int)$d['controls_stock'],
            'allows_addons' => (int)$d['allows_addons'],
            'active' => (int)$d['active'],
            'code' => trim((string)($d['code'] ?? '')) ?: $this->generateProductCode((string)$d['name']),
        ];

        return $this->db->prepare($sql)->execute($params);
    }

    public function create(array $d): bool
    {
        $priceCol = $this->hasColumn('products', 'price') ? 'price' : 'sale_price';
        $costCol = $this->hasColumn('products', 'cost') ? 'cost' : 'cost_price';
        $controlsCol = $this->hasColumn('products', 'controls_stock') ? 'controls_stock' : 'stock_control';
        $addonsCol = $this->hasColumn('products', 'allows_addons') ? 'allows_addons' : 'allow_addons';

        $columns = ['category_id', 'name', 'description', $priceCol, $costCol, $controlsCol, $addonsCol];
        $values = [':category_id', ':name', ':description', ':price', ':cost', ':controls_stock', ':allows_addons'];
        $params = [
            'category_id' => (int)$d['category_id'],
            'name' => (string)$d['name'],
            'description' => (string)($d['description'] ?? ''),
            'price' => (float)$d['price'],
            'cost' => (float)$d['cost'],
            'controls_stock' => (int)$d['controls_stock'],
            'allows_addons' => (int)$d['allows_addons'],
            'active' => (int)($d['active'] ?? 1),
        ];

        if ($this->hasColumn('products', 'code')) {
            $columns[] = 'code';
            $values[] = ':code';
            $params['code'] = trim((string)($d['code'] ?? '')) ?: $this->generateProductCode((string)$d['name']);
        }
        if ($this->hasColumn('products', 'active')) {
            $columns[] = 'active';
            $values[] = ':active';
        }
        if ($this->hasColumn('products', 'created_at')) {
            $columns[] = 'created_at';
            $values[] = 'NOW()';
        }
        if ($this->hasColumn('products', 'updated_at')) {
            $columns[] = 'updated_at';
            $values[] = 'NOW()';
        }

        $sql = 'INSERT INTO products (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';
        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            if ($this->hasColumn('products', 'code') && str_contains(strtolower($e->getMessage()), 'duplicate')) {
                $params['code'] = $this->generateProductCode((string)$d['name']);
                return $stmt->execute($params);
            }
            throw $e;
        }
    }

    private function generateProductCode(string $name): string
    {
        $base = preg_replace('/[^A-Z0-9]/', '', strtoupper(substr(trim($name), 0, 4)));
        if ($base === '') {
            $base = 'PRD';
        }

        return $base . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
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

    private function resolveAddonGroupId(int $addonId): int
    {
        if ($addonId <= 0) {
            return 0;
        }

        if ($this->hasTable('addons')) {
            $groupCol = $this->hasColumn('addons', 'addon_group_id') ? 'addon_group_id' : ($this->hasColumn('addons', 'group_id') ? 'group_id' : null);
            if ($groupCol !== null) {
                $stmt = $this->db->prepare("SELECT {$groupCol} FROM addons WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $addonId]);
                return (int)$stmt->fetchColumn();
            }
        }

        if ($this->hasTable('product_addons') && !$this->hasColumn('product_addons', 'product_id')) {
            $groupCol = $this->hasColumn('product_addons', 'group_id') ? 'group_id' : ($this->hasColumn('product_addons', 'addon_group_id') ? 'addon_group_id' : null);
            if ($groupCol !== null) {
                $stmt = $this->db->prepare("SELECT {$groupCol} FROM product_addons WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $addonId]);
                return (int)$stmt->fetchColumn();
            }
        }

        return 0;
    }
}
