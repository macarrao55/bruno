<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Estoque</h5>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm" id="openStockCreateBtn">Novo insumo</button>
        <button type="button" class="btn btn-outline-primary btn-sm" id="openRecipeCreateBtn">Configurar ficha técnica</button>
    </div>
</div>

<div id="stockCreateModal" class="addons-modal d-none" role="dialog" aria-modal="true" aria-labelledby="stockCreateTitle">
    <div class="addons-backdrop"></div>
    <div class="addons-panel card shadow-lg">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong id="stockCreateTitle">Novo insumo</strong>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="stockCreateCloseBtn">Fechar</button>
        </div>
        <div class="card-body">
            <form method="post" action="<?= base_url('/stock/store') ?>" id="stockCreateForm" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-12"><label class="form-label">Nome</label><input name="name" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Unidade</label><input name="unit" class="form-control" placeholder="kg, un, L" required></div>
                <div class="col-md-4"><label class="form-label">Estoque atual</label><input name="current_stock" type="number" min="0" step="0.001" class="form-control" value="0"></div>
                <div class="col-md-4"><label class="form-label">Estoque mínimo</label><input name="min_stock" type="number" min="0" step="0.001" class="form-control" value="0"></div>
                <div class="col-12"><label class="form-label">Custo médio (R$)</label><input name="average_cost" type="number" min="0" step="0.01" class="form-control" value="0"></div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" id="stockCreateCancelBtn">Cancelar</button>
            <button type="submit" class="btn btn-primary" form="stockCreateForm">Adicionar insumo</button>
        </div>
    </div>
</div>

<div id="recipeCreateModal" class="addons-modal d-none" role="dialog" aria-modal="true" aria-labelledby="recipeCreateTitle">
    <div class="addons-backdrop"></div>
    <div class="addons-panel card shadow-lg">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong id="recipeCreateTitle">Configurar ficha técnica (baixa automática)</strong>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="recipeCreateCloseBtn">Fechar</button>
        </div>
        <div class="card-body">
            <form method="post" action="<?= base_url('/stock/recipes/store') ?>" id="recipeCreateForm" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-12">
                    <label class="form-label">Produto</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach (($products ?? []) as $product): ?>
                            <option value="<?= (int)$product['id'] ?>"><?= htmlspecialchars((string)$product['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Insumo</label>
                    <select name="stock_item_id" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach (($items ?? []) as $item): ?>
                            <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars((string)$item['name']) ?> (<?= htmlspecialchars((string)$item['unit']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Qtd usada por unidade vendida</label>
                    <input name="quantity_used" type="number" min="0.001" step="0.001" class="form-control" required>
                </div>
            </form>
            <small class="text-muted d-block mt-2">Ao salvar, cada venda no PDV fará baixa automática dos insumos vinculados.</small>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" id="recipeCreateCancelBtn">Cancelar</button>
            <button type="submit" class="btn btn-primary" form="recipeCreateForm">Salvar ficha técnica</button>
        </div>
    </div>
</div>

<div id="stockEditModal" class="addons-modal d-none" role="dialog" aria-modal="true" aria-labelledby="stockEditTitle">
    <div class="addons-backdrop"></div>
    <div class="addons-panel card shadow-lg">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong id="stockEditTitle">Editar insumo</strong>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="stockEditCloseBtn">Fechar</button>
        </div>
        <div class="card-body">
            <form method="post" action="<?= base_url('/stock/update') ?>" id="stockEditForm" class="row g-2">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="stock-edit-id">
                <div class="col-md-6"><label class="form-label">Nome</label><input name="name" id="stock-edit-name" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Unidade</label><input name="unit" id="stock-edit-unit" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Atual</label><input name="current_stock" id="stock-edit-current" type="number" step="0.001" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Mínimo</label><input name="min_stock" id="stock-edit-min" type="number" step="0.001" class="form-control"></div>
                <div class="col-12"><label class="form-label">Custo médio</label><input name="average_cost" id="stock-edit-cost" type="number" step="0.01" class="form-control"></div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" id="stockEditCancelBtn">Cancelar</button>
            <button type="submit" class="btn btn-primary" form="stockEditForm">Salvar alterações</button>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Insumos cadastrados</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Insumo</th>
                <th>Unid.</th>
                <th>Atual</th>
                <th>Mín.</th>
                <th>Custo</th>
                <th>Status</th>
                <th style="min-width: 190px;">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Sem itens de estoque cadastrados.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $i): ?>
                    <?php
                    $current = (float)($i['current_stock'] ?? 0);
                    $minimum = (float)($i['min_stock'] ?? 0);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($i['name'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($i['unit'] ?? '-')) ?></td>
                        <td><?= number_format($current, 3, ',', '.') ?></td>
                        <td><?= number_format($minimum, 3, ',', '.') ?></td>
                        <td>R$ <?= number_format((float)($i['average_cost'] ?? 0), 2, ',', '.') ?></td>
                        <td><span class="badge <?= $current <= $minimum ? 'text-bg-danger' : 'text-bg-success' ?>"><?= $current <= $minimum ? 'Baixo' : 'OK' ?></span></td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary stock-edit-btn"
                                data-id="<?= (int)$i['id'] ?>"
                                data-name="<?= htmlspecialchars((string)$i['name'], ENT_QUOTES) ?>"
                                data-unit="<?= htmlspecialchars((string)$i['unit'], ENT_QUOTES) ?>"
                                data-current="<?= htmlspecialchars((string)$i['current_stock'], ENT_QUOTES) ?>"
                                data-min="<?= htmlspecialchars((string)$i['min_stock'], ENT_QUOTES) ?>"
                                data-cost="<?= htmlspecialchars((string)($i['average_cost'] ?? '0'), ENT_QUOTES) ?>"
                            >
                                Editar
                            </button>
                            <form method="post" action="<?= base_url('/stock/delete') ?>" class="d-inline" onsubmit="return confirm('Excluir este insumo?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">Ficha técnica dos produtos</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Produto</th>
                <th>Insumo</th>
                <th>Consumo por venda</th>
                <th>Ação</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($recipes)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Nenhuma ficha técnica configurada.</td></tr>
            <?php else: ?>
                <?php foreach ($recipes as $recipe): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$recipe['product_name']) ?></td>
                        <td><?= htmlspecialchars((string)$recipe['stock_item_name']) ?> (<?= htmlspecialchars((string)$recipe['stock_item_unit']) ?>)</td>
                        <td><?= number_format((float)$recipe['quantity_used'], 3, ',', '.') ?></td>
                        <td>
                            <form method="post" action="<?= base_url('/stock/recipes/delete') ?>" onsubmit="return confirm('Remover insumo da ficha técnica?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$recipe['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
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

  wireSimpleModal('stockCreateModal', 'openStockCreateBtn', 'stockCreateCloseBtn', 'stockCreateCancelBtn');
  wireSimpleModal('recipeCreateModal', 'openRecipeCreateBtn', 'recipeCreateCloseBtn', 'recipeCreateCancelBtn');
  const edit = wireSimpleModal('stockEditModal', null, 'stockEditCloseBtn', 'stockEditCancelBtn');
  if (!edit) return;

  const fields = {
    id: document.getElementById('stock-edit-id'),
    name: document.getElementById('stock-edit-name'),
    unit: document.getElementById('stock-edit-unit'),
    current: document.getElementById('stock-edit-current'),
    min: document.getElementById('stock-edit-min'),
    cost: document.getElementById('stock-edit-cost')
  };

  document.querySelectorAll('.stock-edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      fields.id.value = btn.dataset.id || '';
      fields.name.value = btn.dataset.name || '';
      fields.unit.value = btn.dataset.unit || '';
      fields.current.value = btn.dataset.current || '0';
      fields.min.value = btn.dataset.min || '0';
      fields.cost.value = btn.dataset.cost || '0';
      edit.open();
      fields.name.focus();
    });
  });
})();
</script>
