<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

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

redirect('pages/clientes.php');
