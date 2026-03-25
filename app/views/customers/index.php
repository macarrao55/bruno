<?php $filters = $filters ?? []; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Clientes</h5>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-primary btn-sm" id="openCustomerCreateBtn">Cadastro de cliente</button>
    <button type="button" class="btn btn-outline-primary btn-sm" id="openCustomerFilterBtn">Filtro de clientes</button>
    <a href="<?= base_url('/customers') ?>" class="btn btn-outline-secondary btn-sm">Limpar filtros</a>
  </div>
</div>

<div id="customerCreateModal" class="addons-modal d-none" role="dialog" aria-modal="true" aria-labelledby="customerCreateTitle">
  <div class="addons-backdrop"></div>
  <div class="addons-panel card shadow-lg">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong id="customerCreateTitle">Cadastrar cliente</strong>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="customerCreateCloseBtn">Fechar</button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= base_url('/customers/store') ?>" id="customerCreateForm" class="row g-2"><?= csrf_field() ?>
        <div class="col-md-6"><input name="name" class="form-control" placeholder="Nome" required></div>
        <div class="col-md-6"><input name="phone" class="form-control" placeholder="Telefone" required></div>
        <div class="col-md-4"><input name="zip_code" class="form-control" placeholder="CEP"></div>
        <div class="col-md-2"><input name="address_number" class="form-control" placeholder="Nº"></div>
        <div class="col-md-3"><select name="sex" class="form-select"><option value="">Sexo</option><option value="M">Masculino</option><option value="F">Feminino</option><option value="O">Outro</option></select></div>
        <div class="col-md-3"><input name="neighborhood" class="form-control" placeholder="Bairro"></div>
        <div class="col-md-8"><input name="address" class="form-control" placeholder="Endereço"></div>
        <div class="col-md-4"><input name="birth_date" type="date" class="form-control"></div>
        <div class="col-12"><input name="notes" class="form-control" placeholder="Observações"></div>
      </form>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-light" id="customerCreateCancelBtn">Cancelar</button>
      <button type="submit" class="btn btn-primary" form="customerCreateForm">Salvar</button>
    </div>
  </div>
</div>

<div id="customerFilterModal" class="addons-modal d-none" role="dialog" aria-modal="true" aria-labelledby="customerFilterTitle">
  <div class="addons-backdrop"></div>
  <div class="addons-panel card shadow-lg">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong id="customerFilterTitle">Como deseja filtrar os clientes?</strong>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="customerFilterCloseBtn">Fechar</button>
    </div>
    <div class="card-body">
      <form method="get" action="<?= base_url('/customers') ?>" id="customerFilterForm" class="row g-2">
        <div class="col-md-6"><label class="form-label">Nome</label><input name="name" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filters['name'] ?? '')) ?>"></div>
        <div class="col-md-6"><label class="form-label">Telefone</label><input name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filters['phone'] ?? '')) ?>"></div>
        <div class="col-md-4"><label class="form-label">Bairro</label><input name="neighborhood" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filters['neighborhood'] ?? '')) ?>"></div>
        <div class="col-md-4"><label class="form-label">Sexo</label><select name="sex" class="form-select form-select-sm"><option value="">Todos</option><option value="M" <?= ($filters['sex'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option><option value="F" <?= ($filters['sex'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option><option value="O" <?= ($filters['sex'] ?? '') === 'O' ? 'selected' : '' ?>>Outro</option></select></div>
        <div class="col-md-4"><label class="form-label">Nascimento</label><input name="birth_date" type="date" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filters['birth_date'] ?? '')) ?>"></div>
      </form>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-light" id="customerFilterCancelBtn">Cancelar</button>
      <button type="submit" class="btn btn-primary" form="customerFilterForm">Aplicar filtros</button>
    </div>
  </div>
</div>

<table class="table align-middle">
  <thead><tr><th>Nome</th><th>Telefone</th><th>CEP</th><th>Nº</th><th>Sexo</th><th>Bairro</th><th>Total gasto</th><th>Pedidos</th><th width="120">Ações</th></tr></thead>
  <tbody>
  <?php foreach($customers as $c): ?>
    <tr>
      <td><?= htmlspecialchars($c['name']) ?></td>
      <td><?= htmlspecialchars((string)($c['phone'] ?? '')) ?></td>
      <td><?= htmlspecialchars((string)($c['zip_code'] ?? '')) ?></td>
      <td><?= htmlspecialchars((string)($c['address_number'] ?? '')) ?></td>
      <td><?= htmlspecialchars((string)($c['sex'] ?? '')) ?></td>
      <td><?= htmlspecialchars((string)($c['neighborhood'] ?? '')) ?></td>
      <td>R$ <?= number_format((float)($c['total_spent'] ?? 0),2,',','.') ?></td>
      <td><?= (int)($c['orders_count'] ?? 0) ?></td>
      <td>
        <button type="button" class="btn btn-sm btn-outline-primary customer-edit-btn"
          data-id="<?= (int)$c['id'] ?>"
          data-name="<?= htmlspecialchars((string)$c['name'], ENT_QUOTES) ?>"
          data-phone="<?= htmlspecialchars((string)($c['phone'] ?? ''), ENT_QUOTES) ?>"
          data-zip="<?= htmlspecialchars((string)($c['zip_code'] ?? ''), ENT_QUOTES) ?>"
          data-number="<?= htmlspecialchars((string)($c['address_number'] ?? ''), ENT_QUOTES) ?>"
          data-sex="<?= htmlspecialchars((string)($c['sex'] ?? ''), ENT_QUOTES) ?>"
          data-neighborhood="<?= htmlspecialchars((string)($c['neighborhood'] ?? ''), ENT_QUOTES) ?>"
          data-address="<?= htmlspecialchars((string)($c['address'] ?? ''), ENT_QUOTES) ?>"
          data-birth-date="<?= htmlspecialchars((string)($c['birth_date'] ?? ''), ENT_QUOTES) ?>"
          data-notes="<?= htmlspecialchars((string)($c['notes'] ?? ''), ENT_QUOTES) ?>">
          Editar
        </button>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<div id="customerEditModal" class="addons-modal d-none" role="dialog" aria-modal="true" aria-labelledby="customerEditTitle">
  <div class="addons-backdrop"></div>
  <div class="addons-panel card shadow-lg">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong id="customerEditTitle">Editar cliente</strong>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="customerEditCloseBtn">Fechar</button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= base_url('/customers/update') ?>" id="customerEditForm" class="row g-2"><?= csrf_field() ?>
        <input type="hidden" name="id" id="edit-id">
        <div class="col-md-6"><input name="name" id="edit-name" class="form-control" placeholder="Nome" required></div>
        <div class="col-md-6"><input name="phone" id="edit-phone" class="form-control" placeholder="Telefone" required></div>
        <div class="col-md-4"><input name="zip_code" id="edit-zip" class="form-control" placeholder="CEP"></div>
        <div class="col-md-2"><input name="address_number" id="edit-number" class="form-control" placeholder="Nº"></div>
        <div class="col-md-3"><select name="sex" id="edit-sex" class="form-select"><option value="">Sexo</option><option value="M">Masculino</option><option value="F">Feminino</option><option value="O">Outro</option></select></div>
        <div class="col-md-3"><input name="neighborhood" id="edit-neighborhood" class="form-control" placeholder="Bairro"></div>
        <div class="col-md-8"><input name="address" id="edit-address" class="form-control" placeholder="Endereço"></div>
        <div class="col-md-4"><input name="birth_date" id="edit-birth-date" type="date" class="form-control"></div>
        <div class="col-12"><input name="notes" id="edit-notes" class="form-control" placeholder="Observações"></div>
      </form>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-light" id="customerEditCancelBtn">Cancelar</button>
      <button type="submit" class="btn btn-primary" form="customerEditForm">Salvar alterações</button>
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

    return { modal, open, close };
  }

  wireSimpleModal('customerCreateModal', 'openCustomerCreateBtn', 'customerCreateCloseBtn', 'customerCreateCancelBtn');
  wireSimpleModal('customerFilterModal', 'openCustomerFilterBtn', 'customerFilterCloseBtn', 'customerFilterCancelBtn');

  const edit = wireSimpleModal('customerEditModal', null, 'customerEditCloseBtn', 'customerEditCancelBtn');
  if (!edit) return;

  const fields = {
    id: document.getElementById('edit-id'),
    name: document.getElementById('edit-name'),
    phone: document.getElementById('edit-phone'),
    zip: document.getElementById('edit-zip'),
    number: document.getElementById('edit-number'),
    sex: document.getElementById('edit-sex'),
    neighborhood: document.getElementById('edit-neighborhood'),
    address: document.getElementById('edit-address'),
    birthDate: document.getElementById('edit-birth-date'),
    notes: document.getElementById('edit-notes')
  };

  document.querySelectorAll('.customer-edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      fields.id.value = btn.dataset.id || '';
      fields.name.value = btn.dataset.name || '';
      fields.phone.value = btn.dataset.phone || '';
      fields.zip.value = btn.dataset.zip || '';
      fields.number.value = btn.dataset.number || '';
      fields.sex.value = btn.dataset.sex || '';
      fields.neighborhood.value = btn.dataset.neighborhood || '';
      fields.address.value = btn.dataset.address || '';
      fields.birthDate.value = btn.dataset.birthDate || '';
      fields.notes.value = btn.dataset.notes || '';

      edit.open();
      fields.name.focus();
    });
  });
})();
</script>
