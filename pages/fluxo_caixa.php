<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$db = db();
$hasRecebimentoStatus = tableHasColumn('vendas', 'recebimento_status');

$dataInicio = trim((string) ($_GET['data_inicio'] ?? date('Y-m-01')));
$dataFim = trim((string) ($_GET['data_fim'] ?? date('Y-m-t')));
$tipoFiltro = trim((string) ($_GET['tipo'] ?? 'todos'));

if ($dataInicio === '') {
    $dataInicio = date('Y-m-01');
}
if ($dataFim === '') {
    $dataFim = date('Y-m-t');
}

$movimentos = [];

// Entradas de vendas recebidas no ato.
if ($hasRecebimentoStatus) {
    $sqlVendas = "SELECT DATE(created_at) AS data_mov, 'entrada' AS tipo, 'Venda' AS origem,
                         CONCAT('Venda #', id) AS descricao, total AS valor
                  FROM vendas
                  WHERE DATE(created_at) BETWEEN ? AND ?
                    AND (recebimento_status = 'recebido' OR LOWER(forma_pagamento) IN ('dinheiro','pix','cartão','cartao','cheque'))";
} else {
    $sqlVendas = "SELECT DATE(created_at) AS data_mov, 'entrada' AS tipo, 'Venda' AS origem,
                         CONCAT('Venda #', id) AS descricao, total AS valor
                  FROM vendas
                  WHERE DATE(created_at) BETWEEN ? AND ?";
}
$stmtVendas = $db->prepare($sqlVendas);
$stmtVendas->execute([$dataInicio, $dataFim]);
$movimentos = array_merge($movimentos, $stmtVendas->fetchAll());

// Entradas de recebimentos de crediário (baixas em contas a receber).
$stmtReceber = $db->prepare("SELECT DATE(created_at) AS data_mov, 'entrada' AS tipo, 'Contas a Receber' AS origem,
                                    CONCAT('Recebimento cliente #', cliente_id) AS descricao, valor AS valor
                             FROM contas_receber
                             WHERE DATE(created_at) BETWEEN ? AND ?
                               AND status = 'pago'");
$stmtReceber->execute([$dataInicio, $dataFim]);
$movimentos = array_merge($movimentos, $stmtReceber->fetchAll());

// Saídas de contas a pagar quitadas.
$stmtPagar = $db->prepare("SELECT DATE(created_at) AS data_mov, 'saida' AS tipo, 'Contas a Pagar' AS origem,
                                  CONCAT('Pagamento: ', fornecedor) AS descricao, valor AS valor
                           FROM contas_pagar
                           WHERE DATE(created_at) BETWEEN ? AND ?
                             AND status = 'pago'");
$stmtPagar->execute([$dataInicio, $dataFim]);
$movimentos = array_merge($movimentos, $stmtPagar->fetchAll());

// Saídas de despesas.
$stmtDespesas = $db->prepare("SELECT data AS data_mov, 'saida' AS tipo, 'Despesa' AS origem,
                                     CONCAT('Despesa: ', categoria) AS descricao, valor AS valor
                              FROM despesas
                              WHERE data BETWEEN ? AND ?");
$stmtDespesas->execute([$dataInicio, $dataFim]);
$movimentos = array_merge($movimentos, $stmtDespesas->fetchAll());

if ($tipoFiltro === 'entrada' || $tipoFiltro === 'saida') {
    $movimentos = array_values(array_filter($movimentos, static fn ($m) => ($m['tipo'] ?? '') === $tipoFiltro));
}

usort($movimentos, static function ($a, $b) {
    return strcmp((string) ($b['data_mov'] ?? ''), (string) ($a['data_mov'] ?? ''));
});

$totalEntradas = 0.0;
$totalSaidas = 0.0;
foreach ($movimentos as $mov) {
    $valor = (float) ($mov['valor'] ?? 0);
    if (($mov['tipo'] ?? '') === 'entrada') {
        $totalEntradas += $valor;
    } else {
        $totalSaidas += $valor;
    }
}
$saldoPeriodo = $totalEntradas - $totalSaidas;

renderHeader('Fluxo de Caixa');
?>

<div class="card mb-3"><div class="card-body">
  <form class="row g-2" method="get">
    <div class="col-md-3">
      <label class="form-label">Data inicial</label>
      <input type="date" class="form-control" name="data_inicio" value="<?= e($dataInicio) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Data final</label>
      <input type="date" class="form-control" name="data_fim" value="<?= e($dataFim) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Tipo</label>
      <select class="form-select" name="tipo">
        <option value="todos" <?= $tipoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
        <option value="entrada" <?= $tipoFiltro === 'entrada' ? 'selected' : '' ?>>Entradas</option>
        <option value="saida" <?= $tipoFiltro === 'saida' ? 'selected' : '' ?>>Saídas</option>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end gap-2">
      <button class="btn btn-primary" type="submit">Aplicar</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/pages/fluxo_caixa.php">Limpar</a>
    </div>
  </form>
</div></div>

<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card border-start border-4 border-success"><div class="card-body"><small>Entradas</small><h5 class="mb-0"><?= money($totalEntradas) ?></h5></div></div></div>
  <div class="col-md-4"><div class="card border-start border-4 border-danger"><div class="card-body"><small>Saídas</small><h5 class="mb-0"><?= money($totalSaidas) ?></h5></div></div></div>
  <div class="col-md-4"><div class="card border-start border-4 <?= $saldoPeriodo >= 0 ? 'border-primary' : 'border-warning' ?>"><div class="card-body"><small>Saldo do período</small><h5 class="mb-0"><?= money($saldoPeriodo) ?></h5></div></div></div>
</div>

<div class="card"><div class="card-body table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Data</th>
        <th>Tipo</th>
        <th>Origem</th>
        <th>Descrição</th>
        <th>Valor</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($movimentos as $mov): ?>
        <tr>
          <td><?= e(date('d/m/Y', strtotime((string) $mov['data_mov']))) ?></td>
          <td>
            <?php if (($mov['tipo'] ?? '') === 'entrada'): ?>
              <span class="badge bg-success">Entrada</span>
            <?php else: ?>
              <span class="badge bg-danger">Saída</span>
            <?php endif; ?>
          </td>
          <td><?= e($mov['origem'] ?? '-') ?></td>
          <td><?= e($mov['descricao'] ?? '-') ?></td>
          <td><?= money((float) ($mov['valor'] ?? 0)) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$movimentos): ?>
        <tr><td colspan="5" class="text-center text-muted">Nenhuma movimentação encontrada no período.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div></div>

<?php renderFooter(); ?>
