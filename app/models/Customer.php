<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    public function all(): array
    {
        $phoneCol = $this->hasColumn('customers', 'phone') ? 'phone' : 'phone_main';
        $neighborhoodCol = $this->hasColumn('customers', 'neighborhood') ? 'neighborhood' : 'bairro';
        $addressCol = $this->hasColumn('customers', 'address') ? 'address' : 'endereco';
        $birthCol = $this->hasColumn('customers', 'birth_date') ? 'birth_date' : 'data_nascimento';

        $sql = "SELECT c.*, 
                       c.{$phoneCol} AS phone,
                       c.{$neighborhoodCol} AS neighborhood,
                       c.{$addressCol} AS address,
                       c.{$birthCol} AS birth_date
                FROM customers c
                WHERE c.active=1
                ORDER BY c.name";

        return $this->db->query($sql)->fetchAll();
    }

    public function create(array $d): bool
    {
        $phoneCol = $this->hasColumn('customers', 'phone') ? 'phone' : 'phone_main';
        $neighborhoodCol = $this->hasColumn('customers', 'neighborhood') ? 'neighborhood' : 'bairro';
        $addressCol = $this->hasColumn('customers', 'address') ? 'address' : 'endereco';
        $birthCol = $this->hasColumn('customers', 'birth_date') ? 'birth_date' : 'data_nascimento';

        $stmt = $this->db->prepare("INSERT INTO customers (name,{$phoneCol},{$neighborhoodCol},{$addressCol},{$birthCol},notes,active,created_at,updated_at)
                                    VALUES (:name,:phone,:neighborhood,:address,:birth_date,:notes,1,NOW(),NOW())");
        return $stmt->execute($d);
    }

    public function updateStats(int $id, float $total): void
    {
        $stmt = $this->db->prepare('UPDATE customers SET total_spent=total_spent + :t, orders_count=orders_count+1, updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $id, 't' => $total]);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
