<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;

class CustomersController extends Controller
{
    public function index(): void
    {
        $m = new Customer();
        $this->view('customers/index', ['title' => 'Clientes', 'customers' => $m->all()]);
    }

    public function store(): void
    {
        validate_csrf();
        $m = new Customer();
        $ok = $m->create([
            'name' => trim($_POST['name'] ?? ''),
            'phone_main' => trim($_POST['phone_main'] ?? ''),
            'phone_secondary' => trim($_POST['phone_secondary'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?: null,
            'notes' => trim($_POST['notes'] ?? ''),
            'active' => 1,
        ]);
        flash($ok ? 'success' : 'danger', $ok ? 'Cliente cadastrado.' : 'Erro ao cadastrar cliente.');
        $this->redirect('/customers');
    }
}
