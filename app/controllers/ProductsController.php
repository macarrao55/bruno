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
        $m = new Product();
        $ok = $m->create([
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'code' => trim($_POST['code'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'sale_price' => (float) ($_POST['sale_price'] ?? 0),
            'cost_price' => (float) ($_POST['cost_price'] ?? 0),
            'margin_percent' => (float) ($_POST['margin_percent'] ?? 0),
            'active' => isset($_POST['active']) ? 1 : 0,
            'stock_control' => isset($_POST['stock_control']) ? 1 : 0,
            'allow_addons' => isset($_POST['allow_addons']) ? 1 : 0,
        ]);
        flash($ok ? 'success' : 'danger', $ok ? 'Produto cadastrado.' : 'Erro ao cadastrar produto.');
        $this->redirect('/products');
    }
}
