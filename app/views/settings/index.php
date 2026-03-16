<form method="post" action="<?= base_url('/settings/save') ?>" class="row g-2 mb-4"><?= csrf_field() ?>
<div class="col-md-4"><label>Empresa</label><input name="company_name" class="form-control" value="<?= htmlspecialchars($settings['company_name']) ?>"></div>
<div class="col-md-4"><label>Telefone</label><input name="company_phone" class="form-control" value="<?= htmlspecialchars($settings['company_phone']) ?>"></div>
<div class="col-md-4"><label>Instagram</label><input name="company_instagram" class="form-control" value="<?= htmlspecialchars($settings['company_instagram']) ?>"></div>
<div class="col-md-2"><label>Pontos por real</label><input name="points_per_real" class="form-control" type="number" step="0.1" value="<?= htmlspecialchars($settings['points_per_real']) ?>"></div>
<div class="col-md-2"><label>Dias crediário</label><input name="crediario_due_days" class="form-control" type="number" value="<?= htmlspecialchars($settings['crediario_due_days']) ?>"></div>
<div class="col-md-3"><label>Pix entra no caixa</label><select name="pix_entra_no_caixa" class="form-select"><option value="1" <?= $settings['pix_entra_no_caixa']==='1'?'selected':'' ?>>Sim</option><option value="0" <?= $settings['pix_entra_no_caixa']==='0'?'selected':'' ?>>Não</option></select></div>
<div class="col-md-2 align-self-end"><button class="btn btn-primary">Salvar</button></div>
</form>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><strong>Adicionais do PDV</strong><br><small class="text-muted">1) Crie grupos</small></div>
      <div class="card-body">
        <form method="post" action="<?= base_url('/settings/addon-groups/store') ?>" class="d-flex gap-2"><?= csrf_field() ?>
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
        <form method="post" action="<?= base_url('/settings/addons/store') ?>" class="row g-2"><?= csrf_field() ?>
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
        <form method="post" action="<?= base_url('/settings/addons/attach') ?>" class="row g-2"><?= csrf_field() ?>
          <div class="col-12"><select name="product_id" class="form-select" required><option value="">Produto</option><?php foreach($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-12"><select name="addon_id" class="form-select" required><option value="">Adicional</option><?php foreach($addons as $a): ?><option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-12"><button class="btn btn-outline-primary w-100">Vincular</button></div>
        </form>
      </div>
    </div>
  </div>
</div>
