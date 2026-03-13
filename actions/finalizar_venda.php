<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['administrador', 'gerente', 'caixa', 'vendedor']);

$clienteId = !empty($_POST['cliente_id']) ? (int) $_POST['cliente_id'] : null;
$forma = $_POST['forma_pagamento'] ?? 'Dinheiro';
$recebimentoStatus = $_POST['recebimento_status'] ?? 'recebido';
$descontoTotal = (float) ($_POST['desconto_total'] ?? 0);
$itens = json_decode($_POST['itens_json'] ?? '[]', true);

if (!$itens) {
    redirect('pages/pdv.php');
}

$pdo = db();
$pdo->beginTransaction();

try {
    $subtotal = 0;
    $lucro = 0;
    foreach ($itens as $item) {
        $subtotal += ((float) $item['preco'] * (int) $item['quantidade']) - (float) $item['desconto'];
        $lucro += (((float) $item['preco'] - (float) $item['custo']) * (int) $item['quantidade']) - (float) $item['desconto'];
    }

    $total = $subtotal - $descontoTotal;
    $stmt = $pdo->prepare('INSERT INTO vendas (cliente_id, usuario_id, forma_pagamento, recebimento_status, subtotal, desconto_total, total, lucro) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$clienteId, currentUser()['id'], $forma, $recebimentoStatus, $subtotal, $descontoTotal, $total, $lucro]);
    $vendaId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, desconto, custo_unitario) VALUES (?,?,?,?,?,?)');
    $stockStmt = $pdo->prepare('UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?');

    foreach ($itens as $item) {
        $itemStmt->execute([$vendaId, $item['id'], $item['quantidade'], $item['preco'], $item['desconto'], $item['custo']]);
        $stockStmt->execute([$item['quantidade'], $item['id']]);
    }

    if (($forma === 'Crediário' || $recebimentoStatus === 'na_entrega') && $clienteId) {
        $rec = $pdo->prepare("INSERT INTO contas_receber (cliente_id, valor, vencimento, status) VALUES (?,?,DATE_ADD(CURDATE(), INTERVAL 30 DAY),'aberto')");
        $rec->execute([$clienteId, $total]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
}

redirect('pages/pdv.php');
