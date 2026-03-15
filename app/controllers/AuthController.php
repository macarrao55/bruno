<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->view('auth/login', ['title' => 'Login'], 'layouts/guest');
    }

    public function login(): void
    {
        validate_csrf();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            flash('danger', 'Informe e-mail e senha.');
            $this->redirect('/login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            flash('danger', 'Credenciais inválidas.');
            $this->redirect('/login');
        }

        Auth::login([
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role_name'],
            'permissions' => $user['permissions'],
        ]);

        flash('success', 'Bem-vindo ao MEGA LANCHES ERP!');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
