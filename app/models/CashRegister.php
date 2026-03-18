<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class CashRegister extends Model
{
    public function currentOpen(int $userId): ?array
    {
        $userColumn = $this->resolveCashUserColumn();
        $stmt = $this->db->prepare("SELECT * FROM cash_registers WHERE {$userColumn}=:uid AND status='aberto' ORDER BY id DESC LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function open(int $userId, float $amount, string $notes = ''): bool
    {
        $userColumn = $this->resolveCashUserColumn();
        $stmt = $this->db->prepare("INSERT INTO cash_registers ({$userColumn},opened_at,opening_amount,status,notes,created_at,updated_at) VALUES (:u,NOW(),:a,'aberto',:n,NOW(),NOW())");
        return $stmt->execute(['u' => $userId, 'a' => $amount, 'n' => $notes]);
    }

    public function close(int $id, float $counted): bool
    {
        $stmt = $this->db->prepare(
            "SELECT opening_amount + COALESCE((
                SELECT SUM(CASE WHEN type IN ('entrada','venda','reforco') THEN amount ELSE -amount END)
                FROM cash_movements
                WHERE cash_register_id=:movement_cash_id
            ),0) AS expected
            FROM cash_registers
            WHERE id=:register_id"
        );
        $stmt->execute([
            'movement_cash_id' => $id,
            'register_id' => $id,
        ]);

        $expected = (float)$stmt->fetchColumn();
        $diff = $counted - $expected;

        $sets = [
            'closed_at=NOW()',
            'closing_amount=:c',
            "status='fechado'",
        ];
        $params = [
            'c' => $counted,
            'id' => $id,
        ];

        if ($this->hasColumn('cash_registers', 'expected_amount')) {
            $sets[] = 'expected_amount=:e';
            $params['e'] = $expected;
        }

        if ($this->hasColumn('cash_registers', 'difference_amount')) {
            $sets[] = 'difference_amount=:d';
            $params['d'] = $diff;
        }

        if ($this->hasColumn('cash_registers', 'updated_at')) {
            $sets[] = 'updated_at=NOW()';
        }

        $sql = 'UPDATE cash_registers SET ' . implode(', ', $sets) . ' WHERE id=:id';
        $upd = $this->db->prepare($sql);

        return $upd->execute($params);
    }

    public function addMovement(int $registerId, string $type, string $payment, float $amount, string $desc, ?int $orderId = null): void
    {
        $stmt = $this->db->prepare("INSERT INTO cash_movements (cash_register_id,type,payment_method,amount,description,order_id,created_at,updated_at) VALUES (:r,:t,:p,:a,:d,:o,NOW(),NOW())");
        $stmt->execute(['r' => $registerId, 't' => $type, 'p' => $payment, 'a' => $amount, 'd' => $desc, 'o' => $orderId]);
    }

    public function daySummary(): array
    {
        $sql = "SELECT payment_method, SUM(amount) total FROM cash_movements WHERE DATE(created_at)=CURDATE() GROUP BY payment_method";
        return $this->db->query($sql)->fetchAll();
    }

    private function resolveCashUserColumn(): string
    {
        $stmt = $this->db->prepare(
            "SELECT column_name
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = 'cash_registers'
               AND column_name IN ('user_id', 'operator_id')"
        );
        $stmt->execute();
        $cols = array_column($stmt->fetchAll(), 'column_name');

        if (in_array('user_id', $cols, true)) {
            return 'user_id';
        }

        if (in_array('operator_id', $cols, true)) {
            return 'operator_id';
        }

        return 'user_id';
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
