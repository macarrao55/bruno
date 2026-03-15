<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="mb-3">MEGA LANCHES ERP</h3>
                <form method="post" action="<?= base_url('/login') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Senha</label><input type="password" name="password" class="form-control" required></div>
                    <button class="btn btn-primary w-100">Entrar</button>
                </form>
                <?php foreach (get_flashes() as $flash): ?><div class="alert alert-<?= $flash['type'] ?> mt-3 mb-0"><?= htmlspecialchars($flash['message']) ?></div><?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
