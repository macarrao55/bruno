<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$produtos = db()->query('SELECT * FROM produtos ORDER BY id DESC')->fetchAll();
renderHeader('Produtos');
?>
<div class="card"><div class="card-body">
  <form class="row g-2 mb-3" method="post" action="<?= BASE_URL ?>/actions/save_produto.php">
    <div class="col-md-3"><input name="nome" class="form-control" placeholder="Produto" required></div>
    <div class="col-md-2"><input name="categoria" class="form-control" placeholder="Categoria"></div>
    <div class="col-md-2"><input step="0.01" type="number" name="preco_venda" class="form-control" placeholder="Preço venda"></div>
    <div class="col-md-2"><input step="0.01" type="number" name="preco_revenda" class="form-control" placeholder="Preço revenda"></div>
    <div class="col-md-2"><input step="0.01" type="number" name="custo" class="form-control" placeholder="Custo"></div>
    <div class="col-md-2"><input type="number" name="estoque_minimo" class="form-control" placeholder="Estoque mínimo"></div>
    <div class="col-md-2"><input name="codigo_barras" class="form-control" placeholder="Código barras"></div>
    <div class="col-md-2"><input name="codigo_interno" class="form-control" placeholder="Código interno"></div>
    <div class="col-md-2"><input step="0.01" type="number" name="comissao" class="form-control" placeholder="Comissão %"></div>
    <div class="col-md-2"><button class="btn btn-primary">Salvar produto</button></div>
  </form>
  <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Produto</th><th>Preço</th><th>Estoque</th><th>Mínimo</th><th>Código</th></tr></thead><tbody>
  <?php foreach ($produtos as $p): ?><tr><td><?= e($p['nome']) ?></td><td><?= money((float)$p['preco_venda']) ?></td><td><?= e((string)$p['estoque_atual']) ?></td><td><?= e((string)$p['estoque_minimo']) ?></td><td><?= e($p['codigo_barras']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div></div>
<?php renderFooter(); ?>
