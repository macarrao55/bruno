<div class="card mb-3"><div class="card-body"><form method="post" action="<?= base_url('/products/store') ?>" class="row g-2"><?= csrf_field() ?>
<div class="col-md-3"><input name="name" class="form-control" placeholder="Nome" required></div>
<div class="col-md-2"><select name="category_id" class="form-select"><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><input name="price" type="number" step="0.01" class="form-control" placeholder="Preço" required></div>
<div class="col-md-1"><input name="cost" type="number" step="0.01" class="form-control" placeholder="Custo" required></div>
<div class="col-md-3"><input name="description" class="form-control" placeholder="Descrição"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Cadastrar</button></div>
<div class="col-12 d-flex gap-3"><label><input type="checkbox" name="controls_stock" checked> Controla estoque</label><label><input type="checkbox" name="allows_addons" checked> Aceita adicionais</label><label><input type="checkbox" name="active" checked> Ativo</label></div>
</form></div></div>
<table class="table table-striped"><thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Custo</th></tr></thead><tbody><?php foreach($products as $p): ?><tr><td><?= htmlspecialchars($p['name']) ?></td><td><?= htmlspecialchars($p['category_name']) ?></td><td>R$ <?= number_format($p['price'],2,',','.') ?></td><td>R$ <?= number_format($p['cost'],2,',','.') ?></td></tr><?php endforeach; ?></tbody></table>
