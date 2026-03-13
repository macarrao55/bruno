<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$itens = db()->query('SELECT *, CASE WHEN estoque_atual > 0 THEN ROUND(estoque_atual / 2, 0) ELSE 0 END AS dias_acabar FROM produtos ORDER BY nome')->fetchAll();
renderHeader('Estoque');
?>
<div class="card"><div class="card-body table-responsive">
  <table class="table table-striped"><thead><tr><th>Produto</th><th>Saldo atual</th><th>Mínimo</th><th>Reposição</th><th>Dias para acabar*</th></tr></thead><tbody>
  <?php foreach($itens as $i): $alerta = $i['estoque_atual'] <= $i['estoque_minimo']; ?>
  <tr class="<?= $alerta ? 'table-danger':'' ?>"><td><?= e($i['nome']) ?></td><td><?= e((string)$i['estoque_atual']) ?></td><td><?= e((string)$i['estoque_minimo']) ?></td><td><?= $alerta ? 'Repor urgente' : 'OK' ?></td><td><?= e((string)$i['dias_acabar']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <small class="text-muted">*Estimativa com consumo médio simplificado.</small>
</div></div>
<?php renderFooter(); ?>
