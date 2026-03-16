<div class="card mb-3"><div class="card-body"><form method="post" action="<?= base_url('/products/store') ?>" class="row g-2"><?= csrf_field() ?>
<div class="col-md-3"><input name="name" class="form-control" placeholder="Nome" required></div>
<div class="col-md-2"><select name="category_id" class="form-select"><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><input name="price" type="number" step="0.01" class="form-control" placeholder="Preço" required></div>
<div class="col-md-1"><input name="cost" type="number" step="0.01" class="form-control" placeholder="Custo" required></div>
<div class="col-md-3"><input name="description" class="form-control" placeholder="Descrição"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Cadastrar</button></div>
<div class="col-12 d-flex gap-3"><label><input type="checkbox" name="controls_stock" checked> Controla estoque</label><label><input type="checkbox" name="allows_addons" checked> Aceita adicionais</label><label><input type="checkbox" name="active" checked> Ativo</label></div>
</form></div></div>

<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><strong>Configurar adicionais</strong><br><small class="text-muted">1) Crie grupos</small></div>
      <div class="card-body">
        <form method="post" action="<?= base_url('/products/addon-groups/store') ?>" class="d-flex gap-2"><?= csrf_field() ?>
          <input name="name" class="form-control" placeholder="Ex: Molhos" required>
          <button class="btn btn-outline-primary">Salvar</button>
        </form>
        <ul class="mt-3 mb-0"><?php foreach($addonGroups as $g): ?><li><?= htmlspecialchars($g['name']) ?></li><?php endforeach; ?></ul>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><strong>2) Cadastrar adicional</strong></div>
      <div class="card-body">
        <form method="post" action="<?= base_url('/products/addons/store') ?>" class="row g-2"><?= csrf_field() ?>
          <div class="col-12"><input name="name" class="form-control" placeholder="Ex: Bacon extra" required></div>
          <div class="col-7"><select name="addon_group_id" class="form-select" required><option value="">Grupo</option><?php foreach($addonGroups as $g): ?><option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-5"><input type="number" step="0.01" name="price" class="form-control" placeholder="Preço" required></div>
          <div class="col-12"><button class="btn btn-outline-primary w-100">Cadastrar adicional</button></div>
        </form>
        <ul class="mt-3 mb-0"><?php foreach($addons as $a): ?><li><?= htmlspecialchars($a['name']) ?> (R$ <?= number_format((float)$a['price'],2,',','.') ?>)</li><?php endforeach; ?></ul>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><strong>3) Vincular ao produto</strong></div>
      <div class="card-body">
        <form method="post" action="<?= base_url('/products/addons/attach') ?>" class="row g-2"><?= csrf_field() ?>
          <div class="col-12"><select name="product_id" class="form-select" required><option value="">Produto</option><?php foreach($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-12"><select name="addon_id" class="form-select" required><option value="">Adicional</option><?php foreach($addons as $a): ?><option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-12"><button class="btn btn-outline-primary w-100">Vincular</button></div>
        </form>
      </div>
    </div>
  </div>
</div>

<table class="table table-striped"><thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Custo</th></tr></thead><tbody><?php foreach($products as $p): ?><tr><td><?= htmlspecialchars($p['name']) ?></td><td><?= htmlspecialchars($p['category_name']) ?></td><td>R$ <?= number_format($p['price'],2,',','.') ?></td><td>R$ <?= number_format($p['cost'],2,',','.') ?></td></tr><?php endforeach; ?></tbody></table>
