<div class="card mb-3"><div class="card-body"><form method="post" action="<?= base_url('/users/store') ?>" class="row g-2"><?= csrf_field() ?>
<div class="col-md-3"><input name="name" class="form-control" placeholder="Nome" required></div>
<div class="col-md-3"><input name="email" class="form-control" type="email" placeholder="Email" required></div>
<div class="col-md-2"><input name="password" class="form-control" type="password" placeholder="Senha" required></div>
<div class="col-md-2"><select name="role_id" class="form-select"><?php foreach($roles as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Criar</button></div>
<div class="col-12"><label><input type="checkbox" name="active" checked> Ativo</label></div>
</form></div></div>
<table class="table"><tr><th>Nome</th><th>Email</th><th>Perfil</th><th>Status</th></tr><?php foreach($users as $u): ?><tr><td><?= htmlspecialchars($u['name']) ?></td><td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['role_name']) ?></td><td><?= $u['active'] ? 'Ativo' : 'Inativo' ?></td></tr><?php endforeach; ?></table>
