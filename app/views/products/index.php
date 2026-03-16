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
      <td>
        <button type="button" class="btn btn-sm btn-outline-primary product-edit-btn"
          data-id="<?= (int)$p['id'] ?>"
          data-name="<?= htmlspecialchars((string)$p['name'], ENT_QUOTES) ?>"
          data-category-id="<?= (int)$p['category_id'] ?>"
          data-price="<?= htmlspecialchars((string)$p['price'], ENT_QUOTES) ?>"
          data-cost="<?= htmlspecialchars((string)$p['cost'], ENT_QUOTES) ?>"
          data-description="<?= htmlspecialchars((string)($p['description'] ?? ''), ENT_QUOTES) ?>"
          data-controls-stock="<?= !empty($p['controls_stock']) ? '1' : '0' ?>"
          data-allows-addons="<?= !empty($p['allows_addons']) ? '1' : '0' ?>"
          data-active="<?= !array_key_exists('active', $p) || !empty($p['active']) ? '1' : '0' ?>">
          Editar
        </button>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<div id="productEditModal" class="addons-modal d-none" role="dialog" aria-modal="true" aria-labelledby="productEditTitle">
  <div class="addons-backdrop"></div>
  <div class="addons-panel card shadow-lg">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong id="productEditTitle">Editar produto</strong>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="productEditCloseBtn">Fechar</button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= base_url('/products/update') ?>" id="productEditForm" class="row g-2"><?= csrf_field() ?>
        <input type="hidden" name="id" id="product-edit-id">
        <div class="col-md-4"><input name="name" id="product-edit-name" class="form-control" required></div>
        <div class="col-md-4"><select name="category_id" id="product-edit-category" class="form-select"><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><input name="price" id="product-edit-price" type="number" step="0.01" class="form-control" required></div>
        <div class="col-md-2"><input name="cost" id="product-edit-cost" type="number" step="0.01" class="form-control" required></div>
        <div class="col-12"><input name="description" id="product-edit-description" class="form-control" placeholder="Descrição"></div>
        <div class="col-12 d-flex gap-3">
          <label><input type="checkbox" name="controls_stock" id="product-edit-controls-stock"> Controla estoque</label>
          <label><input type="checkbox" name="allows_addons" id="product-edit-allows-addons"> Aceita adicionais</label>
          <label><input type="checkbox" name="active" id="product-edit-active"> Ativo</label>
        </div>
      </form>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-light" id="productEditCancelBtn">Cancelar</button>
      <button type="submit" class="btn btn-primary" form="productEditForm">Salvar alterações</button>
    </div>
  </div>
</div>

<script>
(() => {
  const modal = document.getElementById('productEditModal');
  if (!modal) return;

  const closeBtn = document.getElementById('productEditCloseBtn');
  const cancelBtn = document.getElementById('productEditCancelBtn');
  const backdrop = modal.querySelector('.addons-backdrop');

  const fields = {
    id: document.getElementById('product-edit-id'),
    name: document.getElementById('product-edit-name'),
    category: document.getElementById('product-edit-category'),
    price: document.getElementById('product-edit-price'),
    cost: document.getElementById('product-edit-cost'),
    description: document.getElementById('product-edit-description'),
    controlsStock: document.getElementById('product-edit-controls-stock'),
    allowsAddons: document.getElementById('product-edit-allows-addons'),
    active: document.getElementById('product-edit-active')
  };

  const close = () => modal.classList.add('d-none');

  closeBtn?.addEventListener('click', close);
  cancelBtn?.addEventListener('click', close);
  backdrop?.addEventListener('click', close);

  document.querySelectorAll('.product-edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      fields.id.value = btn.dataset.id || '';
      fields.name.value = btn.dataset.name || '';
      fields.category.value = btn.dataset.categoryId || '';
      fields.price.value = btn.dataset.price || '0';
      fields.cost.value = btn.dataset.cost || '0';
      fields.description.value = btn.dataset.description || '';
      fields.controlsStock.checked = btn.dataset.controlsStock === '1';
      fields.allowsAddons.checked = btn.dataset.allowsAddons === '1';
      fields.active.checked = btn.dataset.active === '1';

      modal.classList.remove('d-none');
      fields.name.focus();
    });
  });
})();
</script>
