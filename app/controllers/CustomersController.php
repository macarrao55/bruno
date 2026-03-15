<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;

class CustomersController extends Controller
{
    public function index(): void
    {
        $this->view('customers/index', ['title' => 'Clientes', 'customers' => (new Customer())->all()]);
    }

    public function store(): void
    {
        validate_csrf();

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '' || $phone === '') {
            flash('danger', 'Nome e telefone são obrigatórios.');
            $this->redirect('/customers');
        }

        $ok = (new Customer())->create([
            'name' => $name,
            'phone' => $phone,
            'neighborhood' => trim($_POST['neighborhood'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?: null,
            'notes' => trim($_POST['notes'] ?? ''),
        ]);

        flash($ok ? 'success' : 'danger', $ok ? 'Cliente cadastrado' : 'Erro ao cadastrar cliente');
        $this->redirect('/customers');
    }
}
