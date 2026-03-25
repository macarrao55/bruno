<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

class UsersController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['ADMIN','GERENTE']);
        $m = new User();
        $this->view('employees/users', ['title' => 'Usuários', 'users' => $m->all(), 'roles' => $m->roles()]);
    }

    public function store(): void
    {
        Auth::requireRole(['ADMIN']);
        validate_csrf();
        $ok = (new User())->create([
            'role_id' => (int)$_POST['role_id'],
            'name' => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'active' => isset($_POST['active']) ? 1 : 0,
        ]);
        flash($ok ? 'success' : 'danger', $ok ? 'Usuário criado' : 'Erro ao criar usuário');
        $this->redirect('/users');
    }
}
