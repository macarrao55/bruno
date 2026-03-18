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
            $f = $db->prepare('INSERT INTO financial_entries (type,source,source_id,description,category,payment_method,amount,entry_date,status,created_at,updated_at) VALUES ("entrada","recebimento",:sid,:d,"contas_receber","dinheiro",:a,CURDATE(),"realizado",NOW(),NOW())');
            $f->execute(['sid' => $id, 'd' => 'Baixa de conta a receber #' . $id, 'a' => $amount]);
            $db->commit();
            flash('success', 'Conta a receber baixada');
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('danger', 'Erro ao baixar: ' . $e->getMessage());
        }
        $this->redirect('/finance');
    }
}
