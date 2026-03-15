<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['administrador', 'gerente', 'caixa', 'vendedor']);

$clienteId = !empty($_POST['cliente_id']) ? (int) $_POST['cliente_id'] : null;
$forma = $_POST['forma_pagamento'] ?? 'Dinheiro';
$formaNorm = normalizeTextSimple((string) $forma);
$recebimentoStatus = $_POST['recebimento_status'] ?? 'recebido';
$formasPermitidasNorm = array_map(static fn ($f) => normalizeTextSimple((string) $f), getPaymentMethods());
$recebimentoPermitidos = array_keys(getReceivingOptions());
$recebimentoNorm = normalizeTextSimple((string) $recebimentoStatus);
if (!in_array($recebimentoNorm, $recebimentoPermitidos, true)) {
    $recebimentoNorm = normalizeTextSimple((string) getSetting('recebimento_padrao', 'recebido'));
}
$recebimentoStatus = in_array($recebimentoNorm, ['recebido', 'na_entrega'], true) ? $recebimentoNorm : 'recebido';
if (!in_array($formaNorm, $formasPermitidasNorm, true)) {
    redirect('pages/pdv.php?erro=Forma%20de%20pagamento%20inválida');
}
if ($formaNorm === 'crediario') {
    // Regra de negócio: no crediário, recebimento também é crediário (tratado como pendente/na_entrega).
    $recebimentoStatus = 'na_entrega';
}
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
        if ($validade && preg_match('/^\d{4}-\d{2}$/', $validade) === 1) {
            // input month (YYYY-MM) para coluna DATE
            $validade .= '-01';
        }

        if ($hasValidadeGalao) {
            $itemStmt->execute([$vendaId, $item['id'], $item['quantidade'], $item['preco'], $item['desconto'], $item['custo'], $validade]);
        } else {
            $itemStmt->execute([$vendaId, $item['id'], $item['quantidade'], $item['preco'], $item['desconto'], $item['custo']]);
        }
        $stockStmt->execute([$item['quantidade'], $item['id']]);
    }

    if (($formaNorm === 'crediario' || $recebimentoStatus === 'na_entrega') && $clienteId) {
        $diasVencimento = max(1, min(365, (int) getSetting('crediario_dias_vencimento', '30')));
        $rec = $pdo->prepare("INSERT INTO contas_receber (cliente_id, valor, vencimento, status) VALUES (?,?,DATE_ADD(CURDATE(), INTERVAL {$diasVencimento} DAY),'aberto')");
        $rec->execute([$clienteId, $total]);
    }

    $pdo->commit();
    redirect('pages/pdv.php?ok=Venda%20finalizada%20com%20sucesso');
} catch (Throwable $e) {
    $pdo->rollBack();
    redirect('pages/pdv.php?erro=' . urlencode('Falha ao finalizar venda: ' . $e->getMessage()));
}
