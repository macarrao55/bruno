<div class="card mb-3"><div class="card-body"><form method="post" action="<?= base_url('/customers/store') ?>" class="row g-2"><?= csrf_field() ?>
<div class="col-md-3"><input name="name" class="form-control" placeholder="Nome" required></div>
<div class="col-md-2"><input name="phone" class="form-control" placeholder="Telefone" required></div>
<div class="col-md-2"><input name="zip_code" class="form-control" placeholder="CEP"></div>
<div class="col-md-1"><input name="address_number" class="form-control" placeholder="Nº"></div>
<div class="col-md-2"><select name="sex" class="form-select"><option value="">Sexo</option><option value="M">Masculino</option><option value="F">Feminino</option><option value="O">Outro</option></select></div>
<div class="col-md-2"><input name="neighborhood" class="form-control" placeholder="Bairro"></div>
<div class="col-md-4"><input name="address" class="form-control" placeholder="Endereço"></div>
<div class="col-md-2"><input name="birth_date" type="date" class="form-control"></div>
<div class="col-md-4"><input name="notes" class="form-control" placeholder="Observações"></div><div class="col-md-2"><button class="btn btn-primary w-100">Salvar</button></div>
</form></div></div>

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
      <td><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-customer-<?= $c['id'] ?>">Editar</button></td>
    </tr>
    <tr class="collapse" id="edit-customer-<?= $c['id'] ?>">
      <td colspan="9">
        <form method="post" action="<?= base_url('/customers/update') ?>" class="row g-2"><?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <div class="col-md-3"><input name="name" class="form-control" value="<?= htmlspecialchars($c['name']) ?>" required></div>
          <div class="col-md-2"><input name="phone" class="form-control" value="<?= htmlspecialchars((string)($c['phone'] ?? '')) ?>" required></div>
          <div class="col-md-2"><input name="zip_code" class="form-control" value="<?= htmlspecialchars((string)($c['zip_code'] ?? '')) ?>" placeholder="CEP"></div>
          <div class="col-md-1"><input name="address_number" class="form-control" value="<?= htmlspecialchars((string)($c['address_number'] ?? '')) ?>" placeholder="Nº"></div>
          <div class="col-md-2"><select name="sex" class="form-select"><option value="">Sexo</option><option value="M" <?= ($c['sex'] ?? '')==='M' ? 'selected' : '' ?>>Masculino</option><option value="F" <?= ($c['sex'] ?? '')==='F' ? 'selected' : '' ?>>Feminino</option><option value="O" <?= ($c['sex'] ?? '')==='O' ? 'selected' : '' ?>>Outro</option></select></div>
          <div class="col-md-2"><input name="neighborhood" class="form-control" value="<?= htmlspecialchars((string)($c['neighborhood'] ?? '')) ?>" placeholder="Bairro"></div>
          <div class="col-md-4"><input name="address" class="form-control" value="<?= htmlspecialchars((string)($c['address'] ?? '')) ?>" placeholder="Endereço"></div>
          <div class="col-md-2"><input name="birth_date" type="date" class="form-control" value="<?= htmlspecialchars((string)($c['birth_date'] ?? '')) ?>"></div>
          <div class="col-md-4"><input name="notes" class="form-control" value="<?= htmlspecialchars((string)($c['notes'] ?? '')) ?>" placeholder="Observações"></div>
          <div class="col-md-2"><button class="btn btn-success w-100">Salvar edição</button></div>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
