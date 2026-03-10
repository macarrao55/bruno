<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$tipo = $_GET['tipo'] ?? 'vendas';
$queries = [
    'vendas' => 'SELECT id, cliente_id, total, forma_pagamento, recebimento_status, created_at FROM vendas ORDER BY id DESC',
    'estoque' => 'SELECT nome, estoque_atual, estoque_minimo FROM produtos ORDER BY nome',
    'financeiro' => "SELECT 'receber' tipo, valor, vencimento, status FROM contas_receber UNION ALL SELECT 'pagar' tipo, valor, vencimento, status FROM contas_pagar",
    'clientes' => 'SELECT nome, telefone, bairro, cpf, tipo_cliente FROM clientes ORDER BY nome',
    'produtos' => 'SELECT nome, categoria, preco_venda, estoque_atual FROM produtos ORDER BY nome',
    'entregas' => 'SELECT pedido, cliente_id, entregador_id, status, valor_pedido, comissao FROM entregas ORDER BY id DESC',
];

if (!isset($queries[$tipo])) {
    exit('Tipo inválido');
}

$rows = db()->query($queries[$tipo])->fetchAll();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="relatorio_' . $tipo . '.csv"');
$out = fopen('php://output', 'w');
if (!empty($rows)) {
    fputcsv($out, array_keys($rows[0]), ';');
    foreach ($rows as $r) {
        fputcsv($out, $r, ';');
    }
}
fclose($out);
