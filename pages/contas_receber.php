<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$db = db();
$formasPagamento = getPaymentMethods();
// Atualiza automaticamente títulos vencidos em aberto para status atrasado.
$db->exec("UPDATE contas_receber SET status = 'atrasado' WHERE status = 'aberto' AND vencimento < CURDATE()");

$periodoDias = 7;
$clienteFiltro = trim((string) ($_GET['cliente'] ?? ''));
$situacaoFiltro = trim((string) ($_GET['situacao'] ?? 'todos'));

$sql = "SELECT cr.*, c.nome AS cliente_nome, c.telefone,
               CASE
                 WHEN cr.status = 'pago' THEN 'pago'
                 WHEN cr.vencimento < CURDATE() THEN 'atrasado'
                 WHEN cr.vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$periodoDias} DAY) THEN 'a_vencer'
                 ELSE 'aberto'
               END AS situacao_calculada
        FROM contas_receber cr
        LEFT JOIN clientes c ON c.id = cr.cliente_id
        WHERE 1=1";
$params = [];

if ($clienteFiltro !== '') {
    $sql .= ' AND c.nome LIKE ?';
    $params[] = '%' . $clienteFiltro . '%';
}

if ($situacaoFiltro === 'deve') {
    $sql .= " AND cr.status IN ('aberto','atrasado')";
} elseif ($situacaoFiltro === 'atrasado') {
    $sql .= " AND (cr.status = 'atrasado' OR (cr.status = 'aberto' AND cr.vencimento < CURDATE()))";
} elseif ($situacaoFiltro === 'a_vencer') {
    $sql .= " AND cr.status IN ('aberto','atrasado') AND cr.vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$periodoDias} DAY)";
} elseif ($situacaoFiltro === 'pago') {
    $sql .= " AND cr.status = 'pago'";
}

$sql .= ' ORDER BY FIELD(situacao_calculada,\'atrasado\',\'a_vencer\',\'aberto\',\'pago\'), cr.vencimento ASC, cr.id DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$contas = $stmt->fetchAll();

$resumo = ['deve' => 0, 'atrasado' => 0, 'a_vencer' => 0, 'pago' => 0];
$totais = ['deve' => 0.0, 'atrasado' => 0.0, 'a_vencer' => 0.0, 'pago' => 0.0];

foreach ($contas as $conta) {
    $valor = (float) $conta['valor'];
    if (($conta['situacao_calculada'] ?? '') === 'pago') {
        $resumo['pago']++;
        $totais['pago'] += $valor;
        continue;
    }

    $resumo['deve']++;
    $totais['deve'] += $valor;

    if (($conta['situacao_calculada'] ?? '') === 'atrasado') {
        $resumo['atrasado']++;
        $totais['atrasado'] += $valor;
    }
    if (($conta['situacao_calculada'] ?? '') === 'a_vencer') {
        $resumo['a_vencer']++;
        $totais['a_vencer'] += $valor;
    }
}

renderHeader('Contas a Receber');
?>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success"><?= e($_GET['ok']) ?></div>
<?php endif; ?>
<?php if (!empty($_GET['erro'])): ?>
  <div class="alert alert-danger"><?= e($_GET['erro']) ?></div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card border-start border-4 border-primary"><div class="card-body"><small>Clientes que devem</small><h5 class="mb-0"><?= (int) $resumo['deve'] ?> (<?= money($totais['deve']) ?>)</h5></div></div></div>
  <div class="col-md-3"><div class="card border-start border-4 border-danger"><div class="card-body"><small>Clientes atrasados</small><h5 class="mb-0"><?= (int) $resumo['atrasado'] ?> (<?= money($totais['atrasado']) ?>)</h5></div></div></div>
  <div class="col-md-3"><div class="card border-start border-4 border-warning"><div class="card-body"><small>Clientes a vencer (<?= (int) $periodoDias ?> dias)</small><h5 class="mb-0"><?= (int) $resumo['a_vencer'] ?> (<?= money($totais['a_vencer']) ?>)</h5></div></div></div>
  <div class="col-md-3"><div class="card border-start border-4 border-success"><div class="card-body"><small>Contas pagas</small><h5 class="mb-0"><?= (int) $resumo['pago'] ?> (<?= money($totais['pago']) ?>)</h5></div></div></div>
</div>

<div class="card mb-3"><div class="card-body">
  <form class="row g-2" method="get">
    <div class="col-md-4">
      <label class="form-label">Cliente</label>
      <input type="text" class="form-control" name="cliente" value="<?= e($clienteFiltro) ?>" placeholder="Nome do cliente">
    </div>
    <div class="col-md-4">
      <label class="form-label">Situação</label>
      <select class="form-select" name="situacao">
        <option value="todos" <?= $situacaoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
        <option value="deve" <?= $situacaoFiltro === 'deve' ? 'selected' : '' ?>>Clientes que devem</option>
        <option value="atrasado" <?= $situacaoFiltro === 'atrasado' ? 'selected' : '' ?>>Atrasados</option>
        <option value="a_vencer" <?= $situacaoFiltro === 'a_vencer' ? 'selected' : '' ?>>A vencer</option>
        <option value="pago" <?= $situacaoFiltro === 'pago' ? 'selected' : '' ?>>Pagos</option>
      </select>
    </div>
    <div class="col-md-4 d-flex align-items-end gap-2">
      <button class="btn btn-primary" type="submit">Filtrar</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/pages/contas_receber.php">Limpar</a>
    </div>
  </form>
</div></div>

<div class="card"><div class="card-body table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Cliente</th>
        <th>Telefone</th>
        <th>Saldo</th>
        <th>Vencimento</th>
        <th>Status</th>
        <th>Baixa / Pagamento</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($contas as $c): ?>
        <?php
          $situacao = $c['situacao_calculada'] ?? 'aberto';
          $badge = 'bg-secondary';
          $label = 'Aberto';
          if ($situacao === 'atrasado') { $badge = 'bg-danger'; $label = 'Atrasado'; }
          elseif ($situacao === 'a_vencer') { $badge = 'bg-warning text-dark'; $label = 'A vencer'; }
          elseif ($situacao === 'pago') { $badge = 'bg-success'; $label = 'Pago'; }
        ?>
        <tr>
          <td><?= e($c['cliente_nome'] ?? 'Cliente removido') ?></td>
          <td><?= e($c['telefone'] ?? '-') ?></td>
          <td><?= money((float) $c['valor']) ?></td>
          <td><?= e(date('d/m/Y', strtotime((string) $c['vencimento']))) ?></td>
          <td><span class="badge <?= $badge ?>"><?= e($label) ?></span></td>
          <td>
            <?php if ($situacao !== 'pago'): ?>
              <button
                class="btn btn-sm btn-success btn-baixar"
                type="button"
                data-id="<?= (int) $c['id'] ?>"
                data-saldo="<?= e(number_format((float) $c['valor'], 2, '.', '')) ?>"
                data-bs-toggle="modal"
                data-bs-target="#modalBaixa"
              >Dar baixa</button>
            <?php else: ?>
              <span class="text-muted small">Baixado</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div></div>



<div class="modal fade" id="modalBaixa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= BASE_URL ?>/actions/baixar_receber.php">
      <div class="modal-header">
        <h5 class="modal-title">Baixar conta a receber</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="baixaContaId">
        <div class="mb-2">
          <label class="form-label">Saldo atual</label>
          <input class="form-control" id="baixaSaldoAtual" readonly>
        </div>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Juros</label>
            <input type="number" min="0" step="0.01" class="form-control" name="juros" value="0">
          </div>
          <div class="col-md-6">
            <label class="form-label">Desconto</label>
            <input type="number" min="0" step="0.01" class="form-control" name="desconto" value="0">
          </div>
          <div class="col-md-6">
            <label class="form-label">Pagamento parcial (valor pago)</label>
            <input type="number" min="0.01" step="0.01" class="form-control" name="valor_pago" id="baixaValorPago" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Forma de pagamento</label>
            <select class="form-select" name="forma_pagamento" required>
              <option value="">Selecione</option>
              <?php foreach ($formasPagamento as $fp): ?>
                <option value="<?= e($fp) ?>"><?= e($fp) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success">Confirmar baixa</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.btn-baixar').forEach(function(btn){
  btn.addEventListener('click', function(){
    const id = this.dataset.id || '';
    const saldo = Number(this.dataset.saldo || 0);
    document.getElementById('baixaContaId').value = id;
    document.getElementById('baixaSaldoAtual').value = 'R$ ' + saldo.toFixed(2).replace('.', ',');
    document.getElementById('baixaValorPago').value = saldo.toFixed(2);
  });
});
</script>

<?php renderFooter(); ?>
