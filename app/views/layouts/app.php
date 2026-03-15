<?php use App\Core\Auth; ?>
<!doctype html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'MEGA LANCHES ERP') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="d-flex" id="appShell">
    <aside class="sidebar bg-dark text-white p-3">
        <h5 class="fw-bold mb-4">MEGA LANCHES ERP</h5>
        <nav class="nav flex-column gap-1">
            <?php
            $menus = [
                'Dashboard' => '/dashboard', 'PDV' => '/pdv', 'Pedidos' => '/orders', 'Produtos' => '/products', 'Clientes' => '/customers',
                'Caixa' => '/cash', 'Financeiro' => '/finance', 'DRE' => '/dre', 'Conciliação' => '/conciliation', 'Estoque' => '/stock',
                'Fidelidade' => '/loyalty', 'Marketing' => '/marketing', 'Funcionários' => '/employees', 'Relatórios' => '/reports',
                'Configurações' => '/settings', 'Auditoria' => '/audit', 'Backup' => '/backup'
            ];
            foreach ($menus as $label => $link): ?>
                <a class="nav-link text-white-50" href="<?= base_url($link) ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="flex-grow-1 main-content">
        <header class="topbar bg-white border-bottom d-flex justify-content-between align-items-center px-4 py-3">
            <div>
                <h4 class="mb-0"><?= htmlspecialchars($title ?? '') ?></h4>
                <small class="text-muted">Operação balcão, delivery e retirada</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary btn-sm" id="themeToggle">Modo escuro</button>
                <span class="badge bg-primary"><?= htmlspecialchars(Auth::user()['name'] ?? '') ?> (<?= htmlspecialchars(Auth::user()['role'] ?? '') ?>)</span>
                <a class="btn btn-danger btn-sm" href="<?= base_url('/logout') ?>">Sair</a>
            </div>
        </header>

        <section class="p-4">
            <?php foreach (get_flashes() as $flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
            <?= $content ?>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= base_url('/assets/js/app.js') ?>"></script>
</body>
</html>
