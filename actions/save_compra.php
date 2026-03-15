<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('INSERT INTO compras (fornecedor, produto_id, quantidade, custo_unitario, frete, desconto) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$_POST['fornecedor'], $_POST['produto_id'], $_POST['quantidade'], $_POST['custo_unitario'], $_POST['frete'] ?? 0, $_POST['desconto'] ?? 0]);

    $upd = $pdo->prepare('UPDATE produtos SET estoque_atual = estoque_atual + ? WHERE id = ?');
    $upd->execute([$_POST['quantidade'], $_POST['produto_id']]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
}
redirect('pages/compras.php');
