<form method="post" action="<?= base_url('/settings/save') ?>" class="row g-2"><?= csrf_field() ?>
<div class="col-md-4"><label>Empresa</label><input name="company_name" class="form-control" value="<?= htmlspecialchars($settings['company_name']) ?>"></div>
<div class="col-md-4"><label>Telefone</label><input name="company_phone" class="form-control" value="<?= htmlspecialchars($settings['company_phone']) ?>"></div>
<div class="col-md-4"><label>Instagram</label><input name="company_instagram" class="form-control" value="<?= htmlspecialchars($settings['company_instagram']) ?>"></div>
<div class="col-md-2"><label>Pontos por real</label><input name="points_per_real" class="form-control" type="number" step="0.1" value="<?= htmlspecialchars($settings['points_per_real']) ?>"></div>
<div class="col-md-2"><label>Dias crediário</label><input name="crediario_due_days" class="form-control" type="number" value="<?= htmlspecialchars($settings['crediario_due_days']) ?>"></div>
<div class="col-md-3"><label>Pix entra no caixa</label><select name="pix_entra_no_caixa" class="form-select"><option value="1" <?= $settings['pix_entra_no_caixa']==='1'?'selected':'' ?>>Sim</option><option value="0" <?= $settings['pix_entra_no_caixa']==='0'?'selected':'' ?>>Não</option></select></div>
<div class="col-md-2 align-self-end"><button class="btn btn-primary">Salvar</button></div>
</form>
