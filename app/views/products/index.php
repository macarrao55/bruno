<?php $filters = $filters ?? []; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Produtos</h5>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-primary btn-sm" id="openProductCreateBtn">Cadastro de produtos</button>
    <button type="button" class="btn btn-outline-primary btn-sm" id="openProductFilterBtn">Filtro de produtos</button>
    <a href="<?= base_url('/products') ?>" class="btn btn-outline-secondary btn-sm">Limpar filtros</a>
  </div>
</div>

<div id="productCreateModal" class="addons-modal d-none" role="dialog" aria-modal="true" aria-labelledby="productCreateTitle">
  <div class="addons-backdrop"></div>
  <div class="addons-panel card shadow-lg">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong id="productCreateTitle">Cadastrar produto</strong>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="productCreateCloseBtn">Fechar</button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= base_url('/products/store') ?>" id="productCreateForm" class="row g-2"><?= csrf_field() ?>
        <div class="col-md-4"><input name="name" class="form-control" placeholder="Nome" required></div>
        <div class="col-md-2"><input name="code" class="form-control" placeholder="Código (opcional)"></div>
        <div class="col-md-2"><select name="category_id" class="form-select"><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><input name="price" type="number" step="0.01" class="form-control" placeholder="Preço" required></div>
        <div class="col-md-2"><input name="cost" type="number" step="0.01" class="form-control" placeholder="Custo" required></div>
        <div class="col-md-12"><input name="description" class="form-control" placeholder="Descrição"></div>
        <div class="col-12 d-flex gap-3">
          <label><input type="checkbox" name="controls_stock" checked> Controla estoque</label>
          <label><input type="checkbox" name="allows_addons" checked> Aceita adicionais</label>
          <label><input type="checkbox" name="active" checked> Ativo</label>
        </div>
      </form>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-light" id="productCreateCancelBtn">Cancelar</button>
      <button type="submit" class="btn btn-primary" form="productCreateForm">Salvar</button>
    </div>
  </div>
</div>

<div id="productFilterModal" class="addons-modal d-none" role="dialog" aria-modal="true" aria-labelledby="productFilterTitle">
  <div class="addons-backdrop"></div>
  <div class="addons-panel card shadow-lg">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong id="productFilterTitle">Como deseja filtrar os produtos?</strong>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="productFilterCloseBtn">Fechar</button>
    </div>
    <div class="card-body">
      <form method="get" action="<?= base_url('/products') ?>" id="productFilterForm" class="row g-2">
        <div class="col-md-6"><label class="form-label">Nome</label><input name="name" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filters['name'] ?? '')) ?>"></div>
        <div class="col-md-6"><label class="form-label">Categoria</label><select name="category_id" class="form-select form-select-sm"><option value="">Todas</option><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (string)($filters['category_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Controla estoque</label><select name="controls_stock" class="form-select form-select-sm"><option value="">Todos</option><option value="1" <?= ($filters['controls_stock'] ?? '') === '1' ? 'selected' : '' ?>>Sim</option><option value="0" <?= ($filters['controls_stock'] ?? '') === '0' ? 'selected' : '' ?>>Não</option></select></div>
        <div class="col-md-4"><label class="form-label">Aceita adicionais</label><select name="allows_addons" class="form-select form-select-sm"><option value="">Todos</option><option value="1" <?= ($filters['allows_addons'] ?? '') === '1' ? 'selected' : '' ?>>Sim</option><option value="0" <?= ($filters['allows_addons'] ?? '') === '0' ? 'selected' : '' ?>>Não</option></select></div>
        <div class="col-md-4"><label class="form-label">Ativo</label><select name="active" class="form-select form-select-sm"><option value="">Todos</option><option value="1" <?= ($filters['active'] ?? '') === '1' ? 'selected' : '' ?>>Sim</option><option value="0" <?= ($filters['active'] ?? '') === '0' ? 'selected' : '' ?>>Não</option></select></div>
      </form>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-light" id="productFilterCancelBtn">Cancelar</button>
      <button type="submit" class="btn btn-primary" form="productFilterForm">Aplicar filtros</button>
    </div>
  </div>
</div>

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
          data-code="<?= htmlspecialchars((string)($p['code'] ?? ''), ENT_QUOTES) ?>"
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
        <div class="col-md-2"><input name="code" id="product-edit-code" class="form-control" placeholder="Código (opcional)"></div>
        <div class="col-md-2"><select name="category_id" id="product-edit-category" class="form-select"><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
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
  function wireSimpleModal(modalId, openBtnId, closeBtnId, cancelBtnId) {
    const modal = document.getElementById(modalId);
    if (!modal) return null;
    const openBtn = openBtnId ? document.getElementById(openBtnId) : null;
    const closeBtn = closeBtnId ? document.getElementById(closeBtnId) : null;
    const cancelBtn = cancelBtnId ? document.getElementById(cancelBtnId) : null;
    const backdrop = modal.querySelector('.addons-backdrop');

    const open = () => modal.classList.remove('d-none');
    const close = () => modal.classList.add('d-none');

    openBtn?.addEventListener('click', open);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    backdrop?.addEventListener('click', close);

    return { open, close };
  }

  wireSimpleModal('productCreateModal', 'openProductCreateBtn', 'productCreateCloseBtn', 'productCreateCancelBtn');
  wireSimpleModal('productFilterModal', 'openProductFilterBtn', 'productFilterCloseBtn', 'productFilterCancelBtn');

  const editModal = wireSimpleModal('productEditModal', null, 'productEditCloseBtn', 'productEditCancelBtn');
  if (!editModal) return;

  const fields = {
    id: document.getElementById('product-edit-id'),
    name: document.getElementById('product-edit-name'),
    code: document.getElementById('product-edit-code'),
    category: document.getElementById('product-edit-category'),
    price: document.getElementById('product-edit-price'),
    cost: document.getElementById('product-edit-cost'),
    description: document.getElementById('product-edit-description'),
    controlsStock: document.getElementById('product-edit-controls-stock'),
    allowsAddons: document.getElementById('product-edit-allows-addons'),
    active: document.getElementById('product-edit-active')
  };

  document.querySelectorAll('.product-edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      fields.id.value = btn.dataset.id || '';
      fields.name.value = btn.dataset.name || '';
      fields.code.value = btn.dataset.code || '';
      fields.category.value = btn.dataset.categoryId || '';
      fields.price.value = btn.dataset.price || '0';
      fields.cost.value = btn.dataset.cost || '0';
      fields.description.value = btn.dataset.description || '';
      fields.controlsStock.checked = btn.dataset.controlsStock === '1';
      fields.allowsAddons.checked = btn.dataset.allowsAddons === '1';
      fields.active.checked = btn.dataset.active === '1';
      editModal.open();
      fields.name.focus();
    });
  });
})();
</script>
