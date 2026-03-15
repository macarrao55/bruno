<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['administrador', 'gerente', 'caixa']);

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('pages/contas_receber.php?erro=' . urlencode('Conta inválida.'));
}

$juros = max(0, (float) ($_POST['juros'] ?? 0));
$desconto = max(0, (float) ($_POST['desconto'] ?? 0));
$valorPago = (float) ($_POST['valor_pago'] ?? 0);
$formaPagamento = trim((string) ($_POST['forma_pagamento'] ?? ''));

$formasValidas = array_map(static fn ($f) => normalizeTextSimple((string) $f), getPaymentMethods());
if ($formaPagamento === '' || !in_array(normalizeTextSimple($formaPagamento), $formasValidas, true)) {
    redirect('pages/contas_receber.php?erro=' . urlencode('Forma de pagamento inválida.'));
}
if ($valorPago <= 0) {
    redirect('pages/contas_receber.php?erro=' . urlencode('Valor pago deve ser maior que zero.'));
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, valor, vencimento FROM contas_receber WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$conta = $stmt->fetch();

if (!$conta) {
    redirect('pages/contas_receber.php?erro=' . urlencode('Conta não encontrada.'));
}

$saldoAtual = max(0, (float) $conta['valor']);
$saldoAjustado = max(0, $saldoAtual + $juros - $desconto);
$novoSaldo = max(0, $saldoAjustado - $valorPago);

$novoStatus = 'pago';
if ($novoSaldo > 0) {
    $novoStatus = (strtotime((string) $conta['vencimento']) < strtotime(date('Y-m-d'))) ? 'atrasado' : 'aberto';
}

$upd = $pdo->prepare('UPDATE contas_receber SET valor = ?, status = ? WHERE id = ?');
$upd->execute([$novoSaldo, $novoStatus, $id]);

$msg = $novoSaldo > 0
    ? 'Pagamento parcial registrado. Saldo restante: ' . money($novoSaldo)
    : 'Conta baixada com sucesso.';

redirect('pages/contas_receber.php?ok=' . urlencode($msg));
