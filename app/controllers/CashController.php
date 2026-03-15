<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\CashRegister;

class CashController extends Controller
{
    public function index(): void
    {
        $userId = (int)(Auth::user()['id'] ?? 0);
        $m = new CashRegister();
        $this->view('cash/index', ['title' => 'Caixa', 'openCash' => $m->currentOpen($userId), 'summary' => $m->daySummary()]);
    }

    public function open(): void
    {
        validate_csrf();
        $uid = (int)(Auth::user()['id'] ?? 0);
        $m = new CashRegister();
        if ($m->currentOpen($uid)) {
            flash('danger', 'Já existe caixa aberto para este usuário');
            $this->redirect('/cash');
        }
        $ok = $m->open($uid, (float)$_POST['opening_amount'], trim($_POST['notes'] ?? ''));
        flash($ok ? 'success' : 'danger', $ok ? 'Caixa aberto' : 'Erro ao abrir caixa');
        $this->redirect('/cash');
    }

    public function close(): void
    {
        validate_csrf();
        $id = (int)$_POST['cash_id'];
        $ok = (new CashRegister())->close($id, (float)$_POST['closing_amount']);
        flash($ok ? 'success' : 'danger', $ok ? 'Caixa fechado' : 'Erro ao fechar caixa');
        $this->redirect('/cash');
    }
}
