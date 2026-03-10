<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$clientes = db()->query('SELECT * FROM clientes ORDER BY id DESC')->fetchAll();
renderHeader('Clientes');
?>
<div class="card"><div class="card-body">
  <form class="row g-2 mb-3" method="post" action="<?= BASE_URL ?>/actions/save_cliente.php">
    <div class="col-md-3"><input name="nome" class="form-control" placeholder="Nome" required></div>
    <div class="col-md-2"><input name="telefone" class="form-control" placeholder="Telefone"></div>
    <div class="col-md-2"><input name="rua" class="form-control" placeholder="Rua"></div>
    <div class="col-md-1"><input name="numero" class="form-control" placeholder="Nº"></div>
    <div class="col-md-2"><input name="bairro" class="form-control" placeholder="Bairro"></div>
    <div class="col-md-2"><input name="referencia" class="form-control" placeholder="Referência"></div>
    <div class="col-md-2"><input name="cpf" class="form-control" placeholder="CPF"></div>
    <div class="col-md-2">
      <select name="tipo_cliente" class="form-select"><option value="comum">Comum</option><option value="revendedor">Revendedor</option></select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary">Salvar cliente</button></div>
  </form>
  <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Nome</th><th>Telefone</th><th>Bairro</th><th>CPF</th><th>Tipo</th></tr></thead><tbody>
  <?php foreach ($clientes as $c): ?><tr><td><?= e($c['nome']) ?></td><td><?= e($c['telefone']) ?></td><td><?= e($c['bairro']) ?></td><td><?= e($c['cpf']) ?></td><td><?= e($c['tipo_cliente']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div></div>
<?php renderFooter(); ?>
