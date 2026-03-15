<div class="card mb-3"><div class="card-header">Novo cliente</div><div class="card-body"><form method="post" action="<?= base_url('/customers/store') ?>" class="row g-2"><?= csrf_field() ?>
<div class="col-md-4"><input class="form-control" name="name" placeholder="Nome" required></div>
<div class="col-md-3"><input class="form-control" name="phone_main" placeholder="Telefone principal" required></div>
<div class="col-md-3"><input class="form-control" name="phone_secondary" placeholder="Telefone secundário"></div>
<div class="col-md-2"><input class="form-control" type="date" name="birth_date"></div>
<div class="col-10"><textarea class="form-control" name="notes" placeholder="Observações"></textarea></div>
<div class="col-2"><button class="btn btn-primary w-100 h-100">Salvar</button></div>
</form></div></div>
<div class="card"><div class="card-header">Clientes</div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Nome</th><th>Telefone</th><th>Aniversário</th><th>Total gasto</th><th>Pedidos</th></tr></thead><tbody><?php foreach($customers as $c): ?><tr><td><?= htmlspecialchars($c['name']) ?></td><td><?= htmlspecialchars($c['phone_main']) ?></td><td><?= htmlspecialchars((string)$c['birth_date']) ?></td><td>R$ <?= number_format((float)$c['total_spent'],2,',','.') ?></td><td><?= (int)$c['orders_count'] ?></td></tr><?php endforeach; ?></tbody></table></div></div>
