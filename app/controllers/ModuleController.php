<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\GenericList;
use App\Models\Report;
use App\Models\Stock;
use App\Core\Auth;

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
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        $dre = (new Report())->dre($start, $end);

        $this->view('dre/index', [
            'title' => 'DRE',
            'start' => $start,
            'end' => $end,
            'dre' => $dre,
        ]);
    }

    public function conciliation(): void
    {
        $this->view('conciliation/index', ['title' => 'Conciliação']);
    }

    public function stock(): void
    {
        $stock = new Stock();
        $this->view('stock/index', [
            'title' => 'Estoque',
            'items' => $stock->items(),
            'products' => $stock->products(),
            'recipes' => $stock->recipes(),
        ]);
    }

    public function stockStore(): void
    {
        Auth::requireRole(['ADMIN', 'GERENTE']);
        validate_csrf();

        $name = trim((string)($_POST['name'] ?? ''));
        $unit = trim((string)($_POST['unit'] ?? ''));
        if ($name === '' || $unit === '') {
            flash('danger', 'Informe nome e unidade do insumo.');
            $this->redirect('/stock');
        }

        $ok = (new Stock())->createItem([
            'name' => $name,
            'unit' => $unit,
            'current_stock' => (float)($_POST['current_stock'] ?? 0),
            'min_stock' => (float)($_POST['min_stock'] ?? 0),
            'average_cost' => (float)($_POST['average_cost'] ?? 0),
        ]);

        flash($ok ? 'success' : 'danger', $ok ? 'Insumo adicionado com sucesso.' : 'Erro ao adicionar insumo.');
        $this->redirect('/stock');
    }

    public function stockUpdate(): void
    {
        Auth::requireRole(['ADMIN', 'GERENTE']);
        validate_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $unit = trim((string)($_POST['unit'] ?? ''));
        if ($id <= 0 || $name === '' || $unit === '') {
            flash('danger', 'Dados inválidos para editar insumo.');
            $this->redirect('/stock');
        }

        $ok = (new Stock())->updateItem($id, [
            'name' => $name,
            'unit' => $unit,
            'current_stock' => (float)($_POST['current_stock'] ?? 0),
            'min_stock' => (float)($_POST['min_stock'] ?? 0),
            'average_cost' => (float)($_POST['average_cost'] ?? 0),
        ]);

        flash($ok ? 'success' : 'danger', $ok ? 'Insumo atualizado.' : 'Erro ao atualizar insumo.');
        $this->redirect('/stock');
    }

    public function stockDelete(): void
    {
        Auth::requireRole(['ADMIN', 'GERENTE']);
        validate_csrf();

        $ok = (new Stock())->deleteItem((int)($_POST['id'] ?? 0));
        flash($ok ? 'success' : 'danger', $ok ? 'Insumo removido.' : 'Não foi possível remover o insumo.');
        $this->redirect('/stock');
    }

    public function stockRecipeStore(): void
    {
        Auth::requireRole(['ADMIN', 'GERENTE']);
        validate_csrf();

        $ok = (new Stock())->saveRecipe(
            (int)($_POST['product_id'] ?? 0),
            (int)($_POST['stock_item_id'] ?? 0),
            (float)($_POST['quantity_used'] ?? 0)
        );

        flash($ok ? 'success' : 'danger', $ok ? 'Ficha técnica salva. O produto agora baixa estoque nas vendas.' : 'Erro ao salvar ficha técnica.');
        $this->redirect('/stock');
    }

    public function stockRecipeDelete(): void
    {
        Auth::requireRole(['ADMIN', 'GERENTE']);
        validate_csrf();

        $ok = (new Stock())->deleteRecipe((int)($_POST['id'] ?? 0));
        flash($ok ? 'success' : 'danger', $ok ? 'Item removido da ficha técnica.' : 'Erro ao remover item da ficha técnica.');
        $this->redirect('/stock');
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
