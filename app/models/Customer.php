<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    public function all(): array
    {
        $this->ensureExtendedColumns();
        $phoneCol = $this->firstExistingColumn('customers', ['phone', 'phone_main', 'telefone']);
        $neighborhoodCol = $this->firstExistingColumn('customers', ['neighborhood', 'bairro']);
        $addressCol = $this->firstExistingColumn('customers', ['address', 'endereco']);
        $birthCol = $this->firstExistingColumn('customers', ['birth_date', 'data_nascimento']);
        $zipCol = $this->firstExistingColumn('customers', ['zip_code', 'cep']);
        $numberCol = $this->firstExistingColumn('customers', ['address_number', 'number', 'numero']);
        $sexCol = $this->firstExistingColumn('customers', ['sex', 'gender', 'sexo']);

        $phoneExpr = $phoneCol ? "c.{$phoneCol}" : "''";
        $neighExpr = $neighborhoodCol ? "c.{$neighborhoodCol}" : "''";
        $addressExpr = $addressCol ? "c.{$addressCol}" : "''";
        $birthExpr = $birthCol ? "c.{$birthCol}" : 'NULL';
        $zipExpr = $zipCol ? "c.{$zipCol}" : "''";
        $numberExpr = $numberCol ? "c.{$numberCol}" : "''";
        $sexExpr = $sexCol ? "c.{$sexCol}" : "''";

        $activeFilter = $this->hasColumn('customers', 'active') ? 'c.active=1' : '1=1';
        $sql = "SELECT c.*,
                       {$phoneExpr} AS phone,
                       {$neighExpr} AS neighborhood,
                       {$addressExpr} AS address,
                       {$birthExpr} AS birth_date,
                       {$zipExpr} AS zip_code,
                       {$numberExpr} AS address_number,
                       {$sexExpr} AS sex
                FROM customers c
                WHERE {$activeFilter}
                ORDER BY c.name";

        return $this->db->query($sql)->fetchAll();
    }

    public function create(array $d): bool
    {
        return $this->createAndGetId($d) > 0;
    }

    public function createAndGetId(array $d): int
    {
        $this->ensureExtendedColumns();
        $fields = ['name'];
        $params = ['name' => $d['name']];

        foreach ([
            ['phone', ['phone', 'phone_main', 'telefone']],
            ['neighborhood', ['neighborhood', 'bairro']],
            ['address', ['address', 'endereco']],
            ['birth_date', ['birth_date', 'data_nascimento']],
            ['zip_code', ['zip_code', 'cep']],
            ['address_number', ['address_number', 'number', 'numero', 'n']],
            ['sex', ['sex', 'gender', 'sexo']],
        ] as [$paramKey, $candidates]) {
            $col = $this->firstExistingColumn('customers', $candidates);
            if ($col) {
                $fields[] = $col;
                $params[$paramKey] = $d[$paramKey] ?? ($paramKey === 'birth_date' ? null : '');
            }
        }

        if ($this->hasColumn('customers', 'notes')) {
            $fields[] = 'notes';
            $params['notes'] = $d['notes'] ?? '';
        }

        if ($this->hasColumn('customers', 'active')) {
            $fields[] = 'active';
        }
        if ($this->hasColumn('customers', 'created_at')) {
            $fields[] = 'created_at';
        }
        if ($this->hasColumn('customers', 'updated_at')) {
            $fields[] = 'updated_at';
        }

        $placeholders = [];
        foreach ($fields as $f) {
            if ($f === 'active') {
                $placeholders[] = '1';
            } elseif ($f === 'created_at' || $f === 'updated_at') {
                $placeholders[] = 'NOW()';
            } else {
                $p = $this->paramNameForField($f);
                $placeholders[] = ':' . $p;
            }
        }

        $sql = sprintf('INSERT INTO customers (%s) VALUES (%s)', implode(',', $fields), implode(',', $placeholders));
        $stmt = $this->db->prepare($sql);

        $bind = [];
        foreach ($fields as $f) {
            $p = $this->paramNameForField($f);
            if (array_key_exists($p, $params)) {
                $bind[$p] = $params[$p];
            }
        }

        if (!$stmt->execute($bind)) {
            return 0;
        }

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): bool
    {
        $this->ensureExtendedColumns();
        if ($id <= 0) {
            return false;
        }

        $sets = ['name=:name'];
        $params = ['id' => $id, 'name' => $d['name']];

        foreach ([
            ['phone', ['phone', 'phone_main', 'telefone']],
            ['neighborhood', ['neighborhood', 'bairro']],
            ['address', ['address', 'endereco']],
            ['birth_date', ['birth_date', 'data_nascimento']],
            ['zip_code', ['zip_code', 'cep']],
            ['address_number', ['address_number', 'number', 'numero', 'n']],
            ['sex', ['sex', 'gender', 'sexo']],
        ] as [$paramKey, $candidates]) {
            $col = $this->firstExistingColumn('customers', $candidates);
            if ($col) {
                $sets[] = "{$col}=:{$paramKey}";
                $params[$paramKey] = $d[$paramKey] ?? ($paramKey === 'birth_date' ? null : '');
            }
        }

        if ($this->hasColumn('customers', 'notes')) {
            $sets[] = 'notes=:notes';
            $params['notes'] = $d['notes'] ?? '';
        }

        if ($this->hasColumn('customers', 'updated_at')) {
            $sets[] = 'updated_at=NOW()';
        }

        $sql = 'UPDATE customers SET ' . implode(',', $sets) . ' WHERE id=:id';
        return $this->db->prepare($sql)->execute($params);
    }

    public function findById(int $id): ?array
    {
        $phoneCol = $this->firstExistingColumn('customers', ['phone', 'phone_main', 'telefone']);

        $phoneExpr = $phoneCol ? "c.{$phoneCol}" : "''";
        $sql = "SELECT c.*, {$phoneExpr} AS phone FROM customers c WHERE c.id=:id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateStats(int $id, float $total): void
    {
        $stmt = $this->db->prepare('UPDATE customers SET total_spent=total_spent + :t, orders_count=orders_count+1, updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $id, 't' => $total]);
    }

    private function paramNameForField(string $field): string
    {
        return match ($field) {
            'phone', 'phone_main', 'telefone' => 'phone',
            'neighborhood', 'bairro' => 'neighborhood',
            'address', 'endereco' => 'address',
            'birth_date', 'data_nascimento' => 'birth_date',
            'zip_code', 'cep' => 'zip_code',
            'address_number', 'number', 'numero', 'n' => 'address_number',
            'sex', 'gender', 'sexo' => 'sex',
            default => $field,
        };
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }
        return null;
    }


    private function ensureExtendedColumns(): void
    {
        $alter = [];
        if (!$this->hasColumn('customers', 'cep') && !$this->hasColumn('customers', 'zip_code')) {
            $alter[] = 'ADD COLUMN cep VARCHAR(20) NULL';
        }
        if (!$this->hasColumn('customers', 'numero') && !$this->hasColumn('customers', 'address_number') && !$this->hasColumn('customers', 'n')) {
            $alter[] = 'ADD COLUMN numero VARCHAR(20) NULL';
        }
        if (!$this->hasColumn('customers', 'sexo') && !$this->hasColumn('customers', 'sex') && !$this->hasColumn('customers', 'gender')) {
            $alter[] = 'ADD COLUMN sexo VARCHAR(20) NULL';
        }

        if (!$alter) {
            return;
        }

        try {
            $this->db->exec('ALTER TABLE customers ' . implode(', ', $alter));
        } catch (\Throwable $e) {
            // segue sem falhar em ambientes sem permissão de ALTER TABLE
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
