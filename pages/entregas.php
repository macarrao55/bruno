<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$entregadores = db()->query("SELECT id,nome FROM usuarios WHERE nivel='entregador' ORDER BY nome")->fetchAll();
$clientes = db()->query('SELECT id,nome,bairro FROM clientes ORDER BY nome')->fetchAll();
$entregas = db()->query('SELECT e.*, c.nome cliente, c.bairro, u.nome entregador FROM entregas e LEFT JOIN clientes c ON c.id=e.cliente_id LEFT JOIN usuarios u ON u.id=e.entregador_id ORDER BY e.id DESC')->fetchAll();
renderHeader('Entregas');
?>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3" method="post" action="<?= BASE_URL ?>/actions/save_entrega.php">
  <div class="col-md-2"><input name="pedido" class="form-control" placeholder="Pedido" required></div>
  <div class="col-md-3"><select name="cliente_id" class="form-select" required><?php foreach($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nome']) ?> (<?= e($c['bairro']) ?>)</option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select name="entregador_id" class="form-select" required><?php foreach($entregadores as $e): ?><option value="<?= $e['id'] ?>"><?= e($e['nome']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select name="status" class="form-select"><option>preparando</option><option>em rota</option><option>entregue</option></select></div>
  <div class="col-md-2"><input type="number" step="0.01" name="valor_pedido" class="form-control" placeholder="Valor pedido"></div>
  <div class="col-md-2"><button class="btn btn-primary">Registrar entrega</button></div>
</form>
<table class="table table-striped"><thead><tr><th>Pedido</th><th>Cliente</th><th>Bairro</th><th>Entregador</th><th>Status</th><th>Comissão</th></tr></thead><tbody>
<?php foreach($entregas as $e): ?><tr><td><?= e($e['pedido']) ?></td><td><?= e($e['cliente']) ?></td><td><?= e($e['bairro']) ?></td><td><?= e($e['entregador']) ?></td><td><?= e($e['status']) ?></td><td><?= money((float)$e['comissao']) ?></td></tr><?php endforeach; ?>
</tbody></table>
</div></div>
<?php renderFooter(); ?>
