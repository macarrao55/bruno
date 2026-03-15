<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$categoria = trim((string) ($_POST['categoria'] ?? ''));
$validas = ['Geral', 'Galão', 'Botija'];
if (!in_array($categoria, $validas, true)) {
    $categoria = 'Geral';
}

$stmt = db()->prepare('INSERT INTO produtos (nome, categoria, preco_venda, preco_revenda, custo, estoque_minimo, codigo_barras, codigo_interno, comissao) VALUES (?,?,?,?,?,?,?,?,?)');
$stmt->execute([
    $_POST['nome'] ?? '', $categoria, $_POST['preco_venda'] ?? 0, $_POST['preco_revenda'] ?? 0,
    $_POST['custo'] ?? 0, $_POST['estoque_minimo'] ?? 0, $_POST['codigo_barras'] ?? '', $_POST['codigo_interno'] ?? '', $_POST['comissao'] ?? 0
]);
redirect('pages/produtos.php');
