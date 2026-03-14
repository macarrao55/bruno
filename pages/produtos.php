<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$produtos = db()->query('SELECT * FROM produtos ORDER BY id DESC')->fetchAll();
$msg = $_GET['msg'] ?? '';
renderHeader('Produtos');
?>
<div class="card mb-3"><div class="card-body">
  <?php if ($msg === 'updated'): ?><div class="alert alert-success">Produto atualizado com sucesso.</div><?php endif; ?>
  <?php if ($msg === 'deleted'): ?><div class="alert alert-success">Produto excluído com sucesso.</div><?php endif; ?>
  <?php if ($msg === 'linked'): ?><div class="alert alert-warning">Produto não pode ser excluído porque já está vinculado em compras/vendas.</div><?php endif; ?>

  <h6>Novo produto</h6>
  <form class="row g-2 mb-3" method="post" action="<?= BASE_URL ?>/actions/save_produto.php">
    <div class="col-md-3"><input name="nome" class="form-control" placeholder="Produto" required></div>
    <div class="col-md-2">
      <select name="categoria" class="form-select" required>
        <option value="">Categoria</option>
        <option value="Geral">Geral</option>
        <option value="Galão">Galão</option>
        <option value="Botija">Botija</option>
      </select>
    </div>
    <div class="col-md-2"><input step="0.01" type="number" name="preco_venda" class="form-control" placeholder="Preço venda"></div>
    <div class="col-md-2"><input step="0.01" type="number" name="preco_revenda" class="form-control" placeholder="Preço revenda"></div>
    <div class="col-md-2"><input step="0.01" type="number" name="custo" class="form-control" placeholder="Custo"></div>
    <div class="col-md-2"><input type="number" name="estoque_minimo" class="form-control" placeholder="Estoque mínimo"></div>
    <div class="col-md-2"><input name="codigo_barras" class="form-control" placeholder="Código barras"></div>
    <div class="col-md-2"><input name="codigo_interno" class="form-control" placeholder="Código interno"></div>
    <div class="col-md-2"><input step="0.01" type="number" name="comissao" class="form-control" placeholder="Comissão %"></div>
    <div class="col-md-2"><button class="btn btn-primary">Salvar produto</button></div>
  </form>
</div></div>

<div class="card"><div class="card-body">
  <h6>Gerenciar produtos (editar/excluir)</h6>
  <div class="table-responsive"><table class="table table-striped align-middle">
    <thead><tr><th>Produto</th><th>Categoria</th><th>Preço venda</th><th>Preço revenda</th><th>Custo</th><th>Estoque</th><th>Mínimo</th><th>Cód. barras</th><th>Cód. interno</th><th>Comissão</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($produtos as $p): ?>
      <tr>
        <form method="post" action="<?= BASE_URL ?>/actions/update_produto.php">
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <td><input name="nome" class="form-control form-control-sm" value="<?= e($p['nome']) ?>" required></td>
          <td>
            <select name="categoria" class="form-select form-select-sm" required>
              <option value="Geral" <?= $p['categoria'] === 'Geral' ? 'selected' : '' ?>>Geral</option>
              <option value="Galão" <?= $p['categoria'] === 'Galão' ? 'selected' : '' ?>>Galão</option>
              <option value="Botija" <?= $p['categoria'] === 'Botija' ? 'selected' : '' ?>>Botija</option>
            </select>
          </td>
          <td><input step="0.01" type="number" name="preco_venda" class="form-control form-control-sm" value="<?= e((string)$p['preco_venda']) ?>"></td>
          <td><input step="0.01" type="number" name="preco_revenda" class="form-control form-control-sm" value="<?= e((string)$p['preco_revenda']) ?>"></td>
          <td><input step="0.01" type="number" name="custo" class="form-control form-control-sm" value="<?= e((string)$p['custo']) ?>"></td>
          <td><?= e((string)$p['estoque_atual']) ?></td>
          <td><input type="number" name="estoque_minimo" class="form-control form-control-sm" value="<?= e((string)$p['estoque_minimo']) ?>"></td>
          <td><input name="codigo_barras" class="form-control form-control-sm" value="<?= e($p['codigo_barras']) ?>"></td>
          <td><input name="codigo_interno" class="form-control form-control-sm" value="<?= e($p['codigo_interno']) ?>"></td>
          <td><input step="0.01" type="number" name="comissao" class="form-control form-control-sm" value="<?= e((string)$p['comissao']) ?>"></td>
          <td class="d-flex gap-1">
            <button class="btn btn-sm btn-primary" type="submit">Editar</button>
        </form>
            <form method="post" action="<?= BASE_URL ?>/actions/delete_produto.php" onsubmit="return confirm('Excluir produto?');">
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
            </form>
          </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div></div>
<?php renderFooter(); ?>
