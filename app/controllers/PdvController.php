<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Throwable;

class PdvController extends Controller
{
    public function index(): void
    {
        $p = new Product();
        $c = new Customer();
        $this->view('pdv/index', [
            'title' => 'PDV',
            'products' => $p->all(),
            'categories' => $p->categories(),
            'customers' => $c->all(),
        ]);
    }

    public function checkout(): void
    {
        validate_csrf();
        $rawItems = json_decode($_POST['items_json'] ?? '[]', true);
        if (!is_array($rawItems) || $rawItems === []) {
            $this->json(['ok' => false, 'message' => 'Carrinho vazio.'], 422);
        }

        $payload = [
            'customer_id' => (int) ($_POST['customer_id'] ?? 0),
            'order_type' => $_POST['order_type'] ?? 'balcao',
            'payment_method' => $_POST['payment_method'] ?? 'dinheiro',
            'subtotal' => (float) ($_POST['subtotal'] ?? 0),
            'discount_amount' => (float) ($_POST['discount_amount'] ?? 0),
            'delivery_fee' => (float) ($_POST['delivery_fee'] ?? 0),
            'total_amount' => (float) ($_POST['total_amount'] ?? 0),
            'notes' => trim($_POST['notes'] ?? ''),
            'items' => $rawItems,
            'user_id' => (int) (Auth::user()['id'] ?? 0),
        ];

        try {
            $orderId = (new Order())->createFromPdv($payload);
            $this->json(['ok' => true, 'order_id' => $orderId]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Falha ao finalizar venda: ' . $e->getMessage()], 500);
        }
    }
}
