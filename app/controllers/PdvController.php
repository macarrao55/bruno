<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Throwable;

class PdvController extends Controller
{
    public function index(): void
    {
        $p = new Product();
        $user = Auth::user();
        $openCash = (new CashRegister())->currentOpen((int)$user['id']);
        $this->view('pdv/index', [
            'title' => 'PDV',
            'products' => $p->all(),
            'categories' => $p->categories(),
            'customers' => (new Customer())->all(),
            'openCash' => $openCash,
        ]);
    }

    public function addons(): void
    {
        $productId = (int)($_GET['product_id'] ?? 0);
        $this->json((new Product())->addonsByProduct($productId));
    }


    public function quickCustomerStore(): void
    {
        validate_csrf();

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($name === '' || $phone === '') {
            $this->json(['ok' => false, 'message' => 'Nome e telefone são obrigatórios'], 422);
        }

        $customerModel = new Customer();
        $customerId = $customerModel->createAndGetId([
            'name' => $name,
            'phone' => $phone,
            'neighborhood' => trim($_POST['neighborhood'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?: null,
            'notes' => trim($_POST['notes'] ?? ''),
        ]);

        if ($customerId <= 0) {
            $this->json(['ok' => false, 'message' => 'Não foi possível cadastrar o cliente'], 500);
        }

        $customer = $customerModel->findById($customerId);
        $this->json([
            'ok' => true,
            'customer' => [
                'id' => $customerId,
                'name' => $customer['name'] ?? $name,
                'phone' => $customer['phone'] ?? $phone,
            ],
        ]);
    }

    public function checkout(): void
    {
        validate_csrf();
        $user = Auth::user();
        $cash = (new CashRegister())->currentOpen((int)$user['id']);
        if (!$cash) $this->json(['ok' => false, 'message' => 'Abra o caixa antes de vender'], 422);

        $items = json_decode($_POST['items_json'] ?? '[]', true);
        if (!$items || !is_array($items)) $this->json(['ok' => false, 'message' => 'Carrinho vazio'], 422);

        $payload = [
            'customer_id' => (int)($_POST['customer_id'] ?? 0),
            'user_id' => (int)$user['id'],
            'cash_register_id' => (int)$cash['id'],
            'order_type' => $_POST['order_type'] ?? 'balcao',
            'payment_method' => $_POST['payment_method'] ?? 'dinheiro',
            'subtotal' => (float)($_POST['subtotal'] ?? 0),
            'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
            'delivery_fee' => (float)($_POST['delivery_fee'] ?? 0),
            'total_amount' => (float)($_POST['total_amount'] ?? 0),
            'change_amount' => (float)($_POST['change_amount'] ?? 0),
            'notes' => trim($_POST['notes'] ?? ''),
            'items' => $items,
        ];

        if ($payload['payment_method'] === 'crediario' && $payload['customer_id'] <= 0) {
            $this->json(['ok' => false, 'message' => 'Crediário exige cliente cadastrado'], 422);
        }

        try {
            $orderId = (new Order())->createFromPdv($payload);
            $this->json(['ok' => true, 'order_id' => $orderId]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
