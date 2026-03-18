<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;

class OrdersController extends Controller
{
    public function index(): void
    {
        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'customer' => trim((string)($_GET['customer'] ?? '')),
            'order_type' => trim((string)($_GET['order_type'] ?? '')),
            'payment_method' => trim((string)($_GET['payment_method'] ?? '')),
            'date' => trim((string)($_GET['date'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
        ];

        $this->view('orders/index', [
            'title' => 'Pedidos',
            'orders' => (new Order())->all($filters),
            'filters' => $filters,
        ]);
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
