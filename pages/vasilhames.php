<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$clientes=db()->query('SELECT id,nome FROM clientes ORDER BY nome')->fetchAll();
$mov=db()->query('SELECT m.*, c.nome cliente FROM movimentos_vasilhames m LEFT JOIN clientes c ON c.id=m.cliente_id ORDER BY m.id DESC')->fetchAll();
renderHeader('Controle de Galões e Botijões');
?>
<div class="card"><div class="card-body">
<form method="post" action="<?= BASE_URL ?>/actions/save_vasilhame.php" class="row g-2 mb-3">
  <div class="col-md-3"><select name="cliente_id" class="form-select"><?php foreach($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select name="tipo" class="form-select"><option value="galao">Galão</option><option value="botijao">Botijão</option></select></div>
  <div class="col-md-2"><input type="number" name="quantidade" class="form-control" placeholder="Qtd" required></div>
  <div class="col-md-2"><select name="movimento" class="form-select"><option>emprestado</option><option>devolvido</option></select></div>
  <div class="col-md-2"><button class="btn btn-primary">Registrar</button></div>
</form>
<table class="table table-striped"><thead><tr><th>Cliente</th><th>Tipo</th><th>Qtd</th><th>Movimento</th><th>Data</th></tr></thead><tbody>
<?php foreach($mov as $m): ?><tr><td><?= e($m['cliente']) ?></td><td><?= e($m['tipo']) ?></td><td><?= e((string)$m['quantidade']) ?></td><td><?= e($m['movimento']) ?></td><td><?= e($m['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table>
</div></div>
<?php renderFooter(); ?>
