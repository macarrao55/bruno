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

    public function updateStatus(): void
    {
        validate_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'novo';
        $ok = (new Order())->updateStatus($id, $status);
        flash($ok ? 'success' : 'danger', $ok ? 'Status atualizado.' : 'Erro ao atualizar.');
        $this->redirect('/orders');
    }

    public function print80mm(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $orders = (new Order())->all();
        $order = null;
        foreach ($orders as $o) {
            if ((int) $o['id'] === $id) {
                $order = $o;
                break;
            }
        }

        $this->view('orders/print80mm', ['title' => 'Impressão', 'order' => $order], 'layouts/print');
    }
}
