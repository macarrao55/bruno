<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\GenericList;

class ModuleController extends Controller
{
    public function cash(): void
    {
        $this->view('cash/index', ['title' => 'Caixa', 'movements' => (new GenericList())->cashMovements()]);
    }

    public function finance(): void
    {
        $this->view('finance/index', ['title' => 'Fluxo de Caixa', 'entries' => (new GenericList())->financialEntries()]);
    }

    public function dre(): void
    {
        $this->view('dre/index', ['title' => 'DRE']);
    }

    public function conciliation(): void
    {
        $this->view('conciliation/index', ['title' => 'Conciliação']);
    }

    public function stock(): void
    {
        $this->view('stock/index', ['title' => 'Estoque', 'items' => (new GenericList())->stockItems()]);
    }

    public function loyalty(): void
    {
        $this->view('loyalty/index', ['title' => 'Fidelidade', 'rows' => (new GenericList())->loyalty()]);
    }

    public function marketing(): void
    {
        $this->view('marketing/index', ['title' => 'Marketing', 'campaigns' => (new GenericList())->campaigns()]);
    }

    public function employees(): void
    {
        $this->view('employees/index', ['title' => 'Funcionários', 'employees' => (new GenericList())->employees()]);
    }

    public function reports(): void
    {
        $this->view('reports/index', ['title' => 'Relatórios']);
    }

    public function settings(): void
    {
        $this->view('settings/index', ['title' => 'Configurações']);
    }

    public function audit(): void
    {
        $this->view('audit/index', ['title' => 'Auditoria', 'logs' => (new GenericList())->auditLogs()]);
    }

    public function backup(): void
    {
        $this->view('backup/index', ['title' => 'Backup']);
    }
}
