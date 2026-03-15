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
        $this->view('products/index', ['title' => 'Produtos', 'products' => $m->all(), 'categories' => $m->categories()]);
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
}
