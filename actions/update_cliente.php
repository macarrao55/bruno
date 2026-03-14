<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$hasCep = tableHasColumn('clientes', 'cep');

if ($hasCep) {
    $stmt = db()->prepare('UPDATE clientes SET nome=?, telefone=?, cep=?, rua=?, numero=?, bairro=?, referencia=?, cpf=?, tipo_cliente=? WHERE id=?');
    $stmt->execute([
        $_POST['nome'] ?? '',
        $_POST['telefone'] ?? '',
        $_POST['cep'] ?? '',
        $_POST['rua'] ?? '',
        $_POST['numero'] ?? '',
        $_POST['bairro'] ?? '',
        $_POST['referencia'] ?? '',
        $_POST['cpf'] ?? '',
        $_POST['tipo_cliente'] ?? 'comum',
        (int) ($_POST['id'] ?? 0),
    ]);
} else {
    $stmt = db()->prepare('UPDATE clientes SET nome=?, telefone=?, rua=?, numero=?, bairro=?, referencia=?, cpf=?, tipo_cliente=? WHERE id=?');
    $stmt->execute([
        $_POST['nome'] ?? '',
        $_POST['telefone'] ?? '',
        $_POST['rua'] ?? '',
        $_POST['numero'] ?? '',
        $_POST['bairro'] ?? '',
        $_POST['referencia'] ?? '',
        $_POST['cpf'] ?? '',
        $_POST['tipo_cliente'] ?? 'comum',
        (int) ($_POST['id'] ?? 0),
    ]);
}

redirect('pages/clientes.php');
