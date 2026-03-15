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
    redirect('pages/contas_receber.php?erro=' . urlencode('Forma de pagamento inválida para baixa.'));
}

if ($valorPago <= 0) {
    redirect('pages/contas_receber.php?erro=' . urlencode('Informe um valor pago maior que zero.'));
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $pdo->exec("CREATE TABLE IF NOT EXISTS recebimentos_contas_receber (
      id INT AUTO_INCREMENT PRIMARY KEY,
      conta_receber_id INT NOT NULL,
      valor_pago DECIMAL(10,2) NOT NULL,
      juros DECIMAL(10,2) DEFAULT 0,
      desconto DECIMAL(10,2) DEFAULT 0,
      forma_pagamento VARCHAR(40) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (conta_receber_id) REFERENCES contas_receber(id)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $stmt = $pdo->prepare('SELECT * FROM contas_receber WHERE id = ? LIMIT 1 FOR UPDATE');
    $stmt->execute([$id]);
    $conta = $stmt->fetch();

    if (!$conta) {
        throw new RuntimeException('Conta não encontrada.');
    }

    if (($conta['status'] ?? '') === 'pago') {
        throw new RuntimeException('Esta conta já está baixada.');
    }

    $saldoAtual = max(0, (float) ($conta['valor'] ?? 0));
    $saldoComAjuste = max(0, $saldoAtual + $juros - $desconto);
    $abatimento = min($valorPago, $saldoComAjuste);
    $novoSaldo = max(0, $saldoComAjuste - $abatimento);

    $ins = $pdo->prepare('INSERT INTO recebimentos_contas_receber (conta_receber_id, valor_pago, juros, desconto, forma_pagamento) VALUES (?,?,?,?,?)');
    $ins->execute([$id, $abatimento, $juros, $desconto, $formaPagamento]);

    $novoStatus = 'pago';
    if ($novoSaldo > 0) {
        $novoStatus = (strtotime((string) $conta['vencimento']) < strtotime(date('Y-m-d'))) ? 'atrasado' : 'aberto';
    }

    $upd = $pdo->prepare('UPDATE contas_receber SET valor = ?, status = ? WHERE id = ?');
    $upd->execute([$novoSaldo, $novoStatus, $id]);

    $pdo->commit();

    if ($novoSaldo > 0) {
        redirect('pages/contas_receber.php?ok=' . urlencode('Pagamento parcial registrado com sucesso. Saldo restante: ' . money($novoSaldo)));
    }

    redirect('pages/contas_receber.php?ok=' . urlencode('Conta baixada com sucesso.'));
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('pages/contas_receber.php?erro=' . urlencode('Falha ao registrar baixa: ' . $e->getMessage()));
}
