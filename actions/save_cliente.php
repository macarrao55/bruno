<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$hasCep = tableHasColumn('clientes', 'cep');

if ($hasCep) {
    $stmt = db()->prepare('INSERT INTO clientes (nome, telefone, cep, rua, numero, bairro, referencia, cpf, tipo_cliente) VALUES (?,?,?,?,?,?,?,?,?)');
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
    ]);
} else {
    $stmt = db()->prepare('INSERT INTO clientes (nome, telefone, rua, numero, bairro, referencia, cpf, tipo_cliente) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $_POST['nome'] ?? '',
        $_POST['telefone'] ?? '',
        $_POST['rua'] ?? '',
        $_POST['numero'] ?? '',
        $_POST['bairro'] ?? '',
        $_POST['referencia'] ?? '',
        $_POST['cpf'] ?? '',
        $_POST['tipo_cliente'] ?? 'comum',
    ]);
}

$redirectTo = $_POST['redirect_to'] ?? 'pages/clientes.php';
redirect($redirectTo);
