<div class="card mb-3"><div class="card-body"><form method="post" action="<?= base_url('/customers/store') ?>" class="row g-2"><?= csrf_field() ?>
<div class="col-md-3"><input name="name" class="form-control" placeholder="Nome" required></div>
<div class="col-md-2"><input name="phone" class="form-control" placeholder="Telefone" required></div>
<div class="col-md-2"><input name="neighborhood" class="form-control" placeholder="Bairro"></div>
<div class="col-md-3"><input name="address" class="form-control" placeholder="Endereço"></div>
<div class="col-md-2"><input name="birth_date" type="date" class="form-control"></div>
<div class="col-10"><input name="notes" class="form-control" placeholder="Observações"></div><div class="col-2"><button class="btn btn-primary w-100">Salvar</button></div>
</form></div></div>
<table class="table"><thead><tr><th>Nome</th><th>Telefone</th><th>Bairro</th><th>Total gasto</th><th>Pedidos</th></tr></thead><tbody><?php foreach($customers as $c): ?><tr><td><?= htmlspecialchars($c['name']) ?></td><td><?= htmlspecialchars($c['phone']) ?></td><td><?= htmlspecialchars((string)$c['neighborhood']) ?></td><td>R$ <?= number_format($c['total_spent'],2,',','.') ?></td><td><?= (int)$c['orders_count'] ?></td></tr><?php endforeach; ?></tbody></table>
