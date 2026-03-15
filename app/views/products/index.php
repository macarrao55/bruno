<div class="card mb-3"><div class="card-header">Novo produto</div><div class="card-body">
<form method="post" action="<?= base_url('/products/store') ?>" class="row g-2"><?= csrf_field() ?>
<div class="col-md-2"><input class="form-control" name="code" placeholder="Código" required></div>
<div class="col-md-3"><input class="form-control" name="name" placeholder="Nome" required></div>
<div class="col-md-2"><select name="category_id" class="form-select"><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><input class="form-control" name="sale_price" type="number" step="0.01" placeholder="Preço" required></div>
<div class="col-md-2"><input class="form-control" name="cost_price" type="number" step="0.01" placeholder="Custo"></div>
<div class="col-md-1"><button class="btn btn-primary w-100">Salvar</button></div>
<div class="col-12"><textarea class="form-control" name="description" placeholder="Descrição"></textarea></div>
<div class="col-12 d-flex gap-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="active" checked><label class="form-check-label">Ativo</label></div><div class="form-check"><input class="form-check-input" type="checkbox" name="stock_control"><label class="form-check-label">Controla estoque</label></div><div class="form-check"><input class="form-check-input" type="checkbox" name="allow_addons" checked><label class="form-check-label">Aceita adicionais</label></div></div>
</form></div></div>
<div class="card"><div class="card-header">Lista de produtos</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Código</th><th>Produto</th><th>Categoria</th><th>Preço</th><th>Status</th></tr></thead><tbody><?php foreach($products as $p): ?><tr><td><?= htmlspecialchars($p['code']) ?></td><td><?= htmlspecialchars($p['name']) ?></td><td><?= htmlspecialchars($p['category_name']) ?></td><td>R$ <?= number_format((float)$p['sale_price'],2,',','.') ?></td><td><span class="badge <?= (int)$p['active']===1?'text-bg-success':'text-bg-secondary' ?>"><?= (int)$p['active']===1?'Ativo':'Inativo' ?></span></td></tr><?php endforeach; ?></tbody></table></div></div>
