<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Report;

class FinanceController extends Controller
{
    public function index(): void
    {
        $model = new Report();
        $this->view('finance/index', [
            'title' => 'Financeiro',
            'receivables' => $model->receivablesOpen(),
            'lowStock' => $model->lowStock(),
        ]);
    }

    public function receive(): void
    {
        validate_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);

        if ($id <= 0 || $amount <= 0) {
            flash('danger', 'Informe uma conta e um valor válido para baixa.');
            $this->redirect('/finance');
        }

        $db = \App\Core\Database::connection();
        $db->beginTransaction();
        try {
            $u = $db->prepare('UPDATE accounts_receivable SET paid_amount = paid_amount + :amount_add, status = CASE WHEN paid_amount + :amount_compare >= total_amount THEN "pago" ELSE "parcial" END, updated_at=NOW() WHERE id=:id');
            $u->execute([
                'amount_add' => $amount,
                'amount_compare' => $amount,
                'id' => $id,
            ]);
            $fColumns = [];
            $fValues = [];
            $fParams = [];

            $typeColumn = $this->hasColumn($db, 'financial_entries', 'type') ? 'type' : ($this->hasColumn($db, 'financial_entries', 'entry_type') ? 'entry_type' : null);
            if ($typeColumn !== null) {
                $fColumns[] = $typeColumn;
                $fValues[] = ':entry_type';
                $fParams['entry_type'] = 'entrada';
            }

            if ($this->hasColumn($db, 'financial_entries', 'source')) {
                $fColumns[] = 'source';
                $fValues[] = ':source';
                $fParams['source'] = 'recebimento';
            }

            if ($this->hasColumn($db, 'financial_entries', 'source_id')) {
                $fColumns[] = 'source_id';
                $fValues[] = ':source_id';
                $fParams['source_id'] = $id;
            }

            if ($this->hasColumn($db, 'financial_entries', 'description')) {
                $fColumns[] = 'description';
                $fValues[] = ':description';
                $fParams['description'] = 'Baixa de conta a receber #' . $id;
            }

            if ($this->hasColumn($db, 'financial_entries', 'category')) {
                $fColumns[] = 'category';
                $fValues[] = ':category';
                $fParams['category'] = 'contas_receber';
            }

            if ($this->hasColumn($db, 'financial_entries', 'payment_method')) {
                $fColumns[] = 'payment_method';
                $fValues[] = ':payment_method';
                $fParams['payment_method'] = 'dinheiro';
            }

            $amountColumn = $this->hasColumn($db, 'financial_entries', 'amount') ? 'amount' : ($this->hasColumn($db, 'financial_entries', 'value') ? 'value' : null);
            if ($amountColumn !== null) {
                $fColumns[] = $amountColumn;
                $fValues[] = ':amount';
                $fParams['amount'] = $amount;
            }

            if ($this->hasColumn($db, 'financial_entries', 'entry_date')) {
                $fColumns[] = 'entry_date';
                $fValues[] = 'CURDATE()';
            }

            if ($this->hasColumn($db, 'financial_entries', 'status')) {
                $fColumns[] = 'status';
                $fValues[] = ':status';
                $fParams['status'] = 'realizado';
            }

            if ($this->hasColumn($db, 'financial_entries', 'created_at')) {
                $fColumns[] = 'created_at';
                $fValues[] = 'NOW()';
            }

            if ($this->hasColumn($db, 'financial_entries', 'updated_at')) {
                $fColumns[] = 'updated_at';
                $fValues[] = 'NOW()';
            }

            if ($fColumns && $fValues) {
                $fSql = 'INSERT INTO financial_entries (' . implode(',', $fColumns) . ') VALUES (' . implode(',', $fValues) . ')';
                $f = $db->prepare($fSql);
                $f->execute($fParams);
            }
            $db->commit();
            flash('success', 'Conta a receber baixada');
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('danger', 'Erro ao baixar: ' . $e->getMessage());
        }
        $this->redirect('/finance');
    }

    private function hasColumn(\PDO $db, string $table, string $column): bool
    {
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column');
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
