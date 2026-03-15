<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$produtos = db()->query('SELECT id,nome FROM produtos ORDER BY nome')->fetchAll();
$compras = db()->query('SELECT c.*, p.nome produto FROM compras c JOIN produtos p ON p.id=c.produto_id ORDER BY c.id DESC')->fetchAll();
renderHeader('Compras');
?>
<div class="card"><div class="card-body">
  <form class="row g-2 mb-3" method="post" action="<?= BASE_URL ?>/actions/save_compra.php">
    <div class="col-md-2"><input name="fornecedor" class="form-control" placeholder="Fornecedor" required></div>
    <div class="col-md-3"><select name="produto_id" class="form-select" required><?php foreach($produtos as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nome']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-1"><input type="number" name="quantidade" class="form-control" placeholder="Qtd" required></div>
    <div class="col-md-2"><input type="number" step="0.01" name="custo_unitario" class="form-control" placeholder="Custo unitário" required></div>
    <div class="col-md-2"><input type="number" step="0.01" name="frete" class="form-control" placeholder="Frete"></div>
    <div class="col-md-2"><input type="number" step="0.01" name="desconto" class="form-control" placeholder="Desconto"></div>
    <div class="col-md-2"><button class="btn btn-primary">Registrar compra</button></div>
  </form>
  <table class="table table-striped"><thead><tr><th>Fornecedor</th><th>Produto</th><th>Qtd</th><th>Custo</th><th>Data</th></tr></thead><tbody>
  <?php foreach($compras as $c): ?><tr><td><?= e($c['fornecedor']) ?></td><td><?= e($c['produto']) ?></td><td><?= e((string)$c['quantidade']) ?></td><td><?= money((float)$c['custo_unitario']) ?></td><td><?= e($c['created_at']) ?></td></tr><?php endforeach; ?>
  </tbody></table>
</div></div>
<?php renderFooter(); ?>
