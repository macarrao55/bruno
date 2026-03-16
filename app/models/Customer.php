<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    public function all(): array
    {
        $phoneCol = $this->firstExistingColumn('customers', ['phone', 'phone_main']);
        $neighborhoodCol = $this->firstExistingColumn('customers', ['neighborhood', 'bairro']);
        $addressCol = $this->firstExistingColumn('customers', ['address', 'endereco']);
        $birthCol = $this->firstExistingColumn('customers', ['birth_date', 'data_nascimento']);

        $phoneExpr = $phoneCol ? "c.{$phoneCol}" : "''";
        $neighExpr = $neighborhoodCol ? "c.{$neighborhoodCol}" : "''";
        $addressExpr = $addressCol ? "c.{$addressCol}" : "''";
        $birthExpr = $birthCol ? "c.{$birthCol}" : 'NULL';

        $sql = "SELECT c.*,
                       {$phoneExpr} AS phone,
                       {$neighExpr} AS neighborhood,
                       {$addressExpr} AS address,
                       {$birthExpr} AS birth_date
                FROM customers c
                WHERE c.active=1
                ORDER BY c.name";

        return $this->db->query($sql)->fetchAll();
    }

    public function create(array $d): bool
    {
        return $this->createAndGetId($d) > 0;
    }

    public function createAndGetId(array $d): int
    {
        $fields = ['name'];
        $params = ['name' => $d['name']];

        if ($col = $this->firstExistingColumn('customers', ['phone', 'phone_main'])) {
            $fields[] = $col;
            $params['phone'] = $d['phone'] ?? '';
        }
        if ($col = $this->firstExistingColumn('customers', ['neighborhood', 'bairro'])) {
            $fields[] = $col;
            $params['neighborhood'] = $d['neighborhood'] ?? '';
        }
        if ($col = $this->firstExistingColumn('customers', ['address', 'endereco'])) {
            $fields[] = $col;
            $params['address'] = $d['address'] ?? '';
        }
        if ($col = $this->firstExistingColumn('customers', ['birth_date', 'data_nascimento'])) {
            $fields[] = $col;
            $params['birth_date'] = $d['birth_date'] ?? null;
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
            if (isset($params[$p])) {
                $bind[$p] = $params[$p];
            }
        }

        if (!$stmt->execute($bind)) {
            return 0;
        }

        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $phoneCol = $this->firstExistingColumn('customers', ['phone', 'phone_main']);

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
            'phone', 'phone_main' => 'phone',
            'neighborhood', 'bairro' => 'neighborhood',
            'address', 'endereco' => 'address',
            'birth_date', 'data_nascimento' => 'birth_date',
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

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
