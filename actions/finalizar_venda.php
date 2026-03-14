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
    redirect('pages/pdv.php?erro=Carrinho%20vazio');
}

$hasRecebimentoStatus = tableHasColumn('vendas', 'recebimento_status');
$hasValidadeGalao = tableHasColumn('itens_venda', 'validade_galao');

$pdo = db();
$pdo->beginTransaction();

try {
    $subtotal = 0;
    $lucro = 0;

    foreach ($itens as $item) {
        $categoria = strtolower((string) ($item['categoria'] ?? ''));
        $categoria = str_replace(['ã', 'á', 'â', 'à'], ['a', 'a', 'a', 'a'], $categoria);
        $isGalao = str_contains($categoria, 'galao');

        if ($isGalao && $hasValidadeGalao && empty($item['validade_galao'])) {
            throw new RuntimeException('Validade do galão é obrigatória.');
        }

        $subtotal += ((float) $item['preco'] * (int) $item['quantidade']) - (float) ($item['desconto'] ?? 0);
        $lucro += (((float) $item['preco'] - (float) $item['custo']) * (int) $item['quantidade']) - (float) ($item['desconto'] ?? 0);
    }

    $total = $subtotal - $descontoTotal;

    if ($hasRecebimentoStatus) {
        $sqlVenda = 'INSERT INTO vendas (cliente_id, usuario_id, forma_pagamento, recebimento_status, subtotal, desconto_total, total, lucro) VALUES (?,?,?,?,?,?,?,?)';
        $paramsVenda = [$clienteId, currentUser()['id'], $forma, $recebimentoStatus, $subtotal, $descontoTotal, $total, $lucro];
    } else {
        $sqlVenda = 'INSERT INTO vendas (cliente_id, usuario_id, forma_pagamento, subtotal, desconto_total, total, lucro) VALUES (?,?,?,?,?,?,?)';
        $paramsVenda = [$clienteId, currentUser()['id'], $forma, $subtotal, $descontoTotal, $total, $lucro];
    }

    $stmt = $pdo->prepare($sqlVenda);
    $stmt->execute($paramsVenda);
    $vendaId = (int) $pdo->lastInsertId();

    if ($hasValidadeGalao) {
        $itemStmt = $pdo->prepare('INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, desconto, custo_unitario, validade_galao) VALUES (?,?,?,?,?,?,?)');
    } else {
        $itemStmt = $pdo->prepare('INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, desconto, custo_unitario) VALUES (?,?,?,?,?,?)');
    }

    $stockStmt = $pdo->prepare('UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?');

    foreach ($itens as $item) {
        $categoria = strtolower((string) ($item['categoria'] ?? ''));
        $categoria = str_replace(['ã', 'á', 'â', 'à'], ['a', 'a', 'a', 'a'], $categoria);
        $isGalao = str_contains($categoria, 'galao');
        $validade = ($isGalao && $hasValidadeGalao) ? ($item['validade_galao'] ?? null) : null;

        if ($hasValidadeGalao) {
            $itemStmt->execute([$vendaId, $item['id'], $item['quantidade'], $item['preco'], $item['desconto'], $item['custo'], $validade]);
        } else {
            $itemStmt->execute([$vendaId, $item['id'], $item['quantidade'], $item['preco'], $item['desconto'], $item['custo']]);
        }
        $stockStmt->execute([$item['quantidade'], $item['id']]);
    }

    if (($forma === 'Crediário' || $forma === 'Crediario' || $recebimentoStatus === 'na_entrega') && $clienteId) {
        $rec = $pdo->prepare("INSERT INTO contas_receber (cliente_id, valor, vencimento, status) VALUES (?,?,DATE_ADD(CURDATE(), INTERVAL 30 DAY),'aberto')");
        $rec->execute([$clienteId, $total]);
    }

    $pdo->commit();
    redirect('pages/pdv.php?ok=Venda%20finalizada%20com%20sucesso');
} catch (Throwable $e) {
    $pdo->rollBack();
    redirect('pages/pdv.php?erro=' . urlencode('Falha ao finalizar venda: ' . $e->getMessage()));
}
