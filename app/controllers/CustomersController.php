<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;

class CustomersController extends Controller
{
    public function index(): void
    {
        $filters = [
            'name' => trim((string)($_GET['name'] ?? '')),
            'phone' => trim((string)($_GET['phone'] ?? '')),
            'neighborhood' => trim((string)($_GET['neighborhood'] ?? '')),
            'sex' => trim((string)($_GET['sex'] ?? '')),
            'birth_date' => trim((string)($_GET['birth_date'] ?? '')),
        ];

        $this->view('customers/index', [
            'title' => 'Clientes',
            'customers' => (new Customer())->all($filters),
            'filters' => $filters,
        ]);
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

        $ok = (new Customer())->create($this->payload());

        flash($ok ? 'success' : 'danger', $ok ? 'Cliente cadastrado' : 'Erro ao cadastrar cliente');
        $this->redirect('/customers');
    }

    public function update(): void
    {
        validate_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($id <= 0 || $name === '' || $phone === '') {
            flash('danger', 'Dados inválidos para atualização do cliente.');
            $this->redirect('/customers');
        }

        $ok = (new Customer())->update($id, $this->payload());
        flash($ok ? 'success' : 'danger', $ok ? 'Cliente atualizado' : 'Erro ao atualizar cliente');
        $this->redirect('/customers');
    }

    private function payload(): array
    {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'neighborhood' => trim($_POST['neighborhood'] ?? ($_POST['bairro'] ?? '')),
            'address' => trim($_POST['address'] ?? ($_POST['endereco'] ?? '')),
            'address_number' => trim($_POST['address_number'] ?? ($_POST['numero'] ?? ($_POST['n'] ?? ''))),
            'zip_code' => trim($_POST['zip_code'] ?? ($_POST['cep'] ?? '')),
            'sex' => trim($_POST['sex'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?: null,
            'notes' => trim($_POST['notes'] ?? ''),
        ];
    }
}
