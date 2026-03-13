<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$receber = db()->query('SELECT cr.*, c.nome cliente FROM contas_receber cr LEFT JOIN clientes c ON c.id=cr.cliente_id ORDER BY cr.vencimento')->fetchAll();
$pagar = db()->query('SELECT * FROM contas_pagar ORDER BY vencimento')->fetchAll();
$despesas = db()->query('SELECT * FROM despesas ORDER BY data DESC')->fetchAll();
$clientes = db()->query('SELECT id,nome FROM clientes ORDER BY nome')->fetchAll();
renderHeader('Financeiro');
?>
<div class="row g-3">
  <div class="col-md-4"><div class="card"><div class="card-body">
    <h6>Contas a Receber</h6>
    <form method="post" action="<?= BASE_URL ?>/actions/save_receber.php" class="mb-2">
      <select name="cliente_id" class="form-select mb-1"><?php foreach($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option><?php endforeach; ?></select>
      <input type="number" step="0.01" name="valor" class="form-control mb-1" placeholder="Valor" required>
      <input type="date" name="vencimento" class="form-control mb-1" required>
      <button class="btn btn-sm btn-primary">Adicionar</button>
    </form>
    <?php foreach($receber as $r): ?><div class="border rounded p-2 mb-1 small"><?= e($r['cliente'] ?? 'Cliente') ?> - <?= money((float)$r['valor']) ?> - <?= e($r['status']) ?></div><?php endforeach; ?>
  </div></div></div>

  <div class="col-md-4"><div class="card"><div class="card-body">
    <h6>Contas a Pagar</h6>
    <form method="post" action="<?= BASE_URL ?>/actions/save_pagar.php" class="mb-2">
      <input name="fornecedor" class="form-control mb-1" placeholder="Fornecedor" required>
      <input type="number" step="0.01" name="valor" class="form-control mb-1" placeholder="Valor" required>
      <input type="date" name="vencimento" class="form-control mb-1" required>
      <button class="btn btn-sm btn-primary">Adicionar</button>
    </form>
    <?php foreach($pagar as $p): ?><div class="border rounded p-2 mb-1 small"><?= e($p['fornecedor']) ?> - <?= money((float)$p['valor']) ?> - <?= e($p['status']) ?></div><?php endforeach; ?>
  </div></div></div>

  <div class="col-md-4"><div class="card"><div class="card-body">
    <h6>Despesas</h6>
    <form method="post" action="<?= BASE_URL ?>/actions/save_despesa.php" class="mb-2">
      <input name="categoria" class="form-control mb-1" placeholder="Categoria" required>
      <input type="number" step="0.01" name="valor" class="form-control mb-1" placeholder="Valor" required>
      <input type="date" name="data" class="form-control mb-1" required>
      <button class="btn btn-sm btn-primary">Adicionar</button>
    </form>
    <?php foreach($despesas as $d): ?><div class="border rounded p-2 mb-1 small"><?= e($d['categoria']) ?> - <?= money((float)$d['valor']) ?> - <?= e($d['data']) ?></div><?php endforeach; ?>
  </div></div></div>
</div>
<?php renderFooter(); ?>
