<div class="card mb-3"><div class="card-body"><form method="post" action="<?= base_url('/products/store') ?>" class="row g-2"><?= csrf_field() ?>
<div class="col-md-3"><input name="name" class="form-control" placeholder="Nome" required></div>
<div class="col-md-2"><select name="category_id" class="form-select"><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><input name="price" type="number" step="0.01" class="form-control" placeholder="Preço" required></div>
<div class="col-md-1"><input name="cost" type="number" step="0.01" class="form-control" placeholder="Custo" required></div>
<div class="col-md-3"><input name="description" class="form-control" placeholder="Descrição"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Cadastrar</button></div>
<div class="col-12 d-flex gap-3"><label><input type="checkbox" name="controls_stock" checked> Controla estoque</label><label><input type="checkbox" name="allows_addons" checked> Aceita adicionais</label><label><input type="checkbox" name="active" checked> Ativo</label></div>
</form></div></div>

<table class="table table-striped align-middle">
  <thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Custo</th><th width="180">Ações</th></tr></thead>
  <tbody>
  <?php foreach($products as $p): ?>
    <tr>
      <td><?= htmlspecialchars($p['name']) ?></td>
      <td><?= htmlspecialchars($p['category_name']) ?></td>
      <td>R$ <?= number_format($p['price'],2,',','.') ?></td>
      <td>R$ <?= number_format($p['cost'],2,',','.') ?></td>
      <td><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-product-<?= $p['id'] ?>">Editar</button></td>
    </tr>
    <tr class="collapse" id="edit-product-<?= $p['id'] ?>">
      <td colspan="5">
        <form method="post" action="<?= base_url('/products/update') ?>" class="row g-2"><?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <div class="col-md-3"><input name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required></div>
          <div class="col-md-2"><select name="category_id" class="form-select"><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$p['category_id']===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-1"><input name="price" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars((string)$p['price']) ?>" required></div>
          <div class="col-md-1"><input name="cost" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars((string)$p['cost']) ?>" required></div>
          <div class="col-md-3"><input name="description" class="form-control" value="<?= htmlspecialchars((string)($p['description'] ?? '')) ?>"></div>
          <div class="col-md-2"><button class="btn btn-success w-100">Salvar edição</button></div>
          <div class="col-12 d-flex gap-3">
            <label><input type="checkbox" name="controls_stock" <?= !empty($p['controls_stock']) ? 'checked' : '' ?>> Controla estoque</label>
            <label><input type="checkbox" name="allows_addons" <?= !empty($p['allows_addons']) ? 'checked' : '' ?>> Aceita adicionais</label>
            <label><input type="checkbox" name="active" <?= !array_key_exists('active', $p) || !empty($p['active']) ? 'checked' : '' ?>> Ativo</label>
          </div>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
