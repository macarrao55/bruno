<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;

class OrdersController extends Controller
{
    public function index(): void
    {
        $this->view('orders/index', ['title' => 'Pedidos', 'orders' => (new Order())->all()]);
    }

    public function status(): void
    {
        validate_csrf();
        $ok = (new Order())->changeStatus((int)$_POST['id'], $_POST['status']);
        flash($ok ? 'success' : 'danger', $ok ? 'Status alterado' : 'Erro ao alterar status');
        $this->redirect('/orders');
    }

    public function printClient(): void
    {
        $order = (new Order())->findWithItems((int)($_GET['id'] ?? 0));
        $this->view('orders/print80mm', ['order' => $order, 'title' => 'Impressão cliente', 'mode' => 'cliente'], 'layouts/print');
    }

    public function printKitchen(): void
    {
        $order = (new Order())->findWithItems((int)($_GET['id'] ?? 0));
        $this->view('orders/print80mm', ['order' => $order, 'title' => 'Impressão cozinha', 'mode' => 'cozinha'], 'layouts/print');
    }
}
