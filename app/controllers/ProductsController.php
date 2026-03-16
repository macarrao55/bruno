<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class ProductsController extends Controller
{
    public function index(): void
    {
        $m = new Product();
        $this->view('products/index', [
            'title' => 'Produtos',
            'products' => $m->all(),
            'categories' => $m->categories(),
            'addonGroups' => $m->addonGroups(),
            'addons' => $m->allAddons(),
        ]);
    }

    public function store(): void
    {
        validate_csrf();
        $ok = (new Product())->create([
            'category_id' => (int)$_POST['category_id'],
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'price' => (float)$_POST['price'],
            'cost' => (float)$_POST['cost'],
            'controls_stock' => isset($_POST['controls_stock']) ? 1 : 0,
            'allows_addons' => isset($_POST['allows_addons']) ? 1 : 0,
            'active' => isset($_POST['active']) ? 1 : 0,
        ]);
        flash($ok ? 'success' : 'danger', $ok ? 'Produto cadastrado' : 'Erro ao cadastrar');
        $this->redirect('/products');
    }

    public function storeAddonGroup(): void
    {
        validate_csrf();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            flash('danger', 'Nome do grupo é obrigatório.');
            $this->redirect('/products');
        }

        $ok = (new Product())->createAddonGroup($name);
        flash($ok ? 'success' : 'danger', $ok ? 'Grupo de adicionais criado.' : 'Erro ao criar grupo.');
        $this->redirect('/products');
    }

    public function storeAddon(): void
    {
        validate_csrf();
        $name = trim($_POST['name'] ?? '');
        $groupId = (int)($_POST['addon_group_id'] ?? 0);

        if ($name === '' || $groupId <= 0) {
            flash('danger', 'Preencha nome e grupo do adicional.');
            $this->redirect('/products');
        }

        $ok = (new Product())->createAddon($groupId, $name, (float)($_POST['price'] ?? 0));
        flash($ok ? 'success' : 'danger', $ok ? 'Adicional cadastrado.' : 'Erro ao cadastrar adicional.');
        $this->redirect('/products');
    }

    public function attachAddon(): void
    {
        validate_csrf();

        $ok = (new Product())->attachAddonToProduct((int)($_POST['product_id'] ?? 0), (int)($_POST['addon_id'] ?? 0));
        flash($ok ? 'success' : 'danger', $ok ? 'Adicional vinculado ao produto.' : 'Erro ao vincular adicional.');
        $this->redirect('/products');
    }
}
