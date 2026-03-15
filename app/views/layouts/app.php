<?php use App\Core\Auth; ?>
<!doctype html><html lang="pt-BR" data-bs-theme="light"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title ?? 'MEGA LANCHES ERP') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= base_url('/assets/css/app.css') ?>" rel="stylesheet"></head>
<body><div class="d-flex">
<aside class="sidebar bg-dark text-white p-3"><h5>MEGA LANCHES ERP</h5>
<nav class="nav flex-column small">
<a class="nav-link text-white" href="<?= base_url('/dashboard') ?>">Dashboard</a>
<a class="nav-link text-white" href="<?= base_url('/cash') ?>">Caixa</a>
<a class="nav-link text-white" href="<?= base_url('/pdv') ?>">PDV</a>
<a class="nav-link text-white" href="<?= base_url('/orders') ?>">Pedidos</a>
<a class="nav-link text-white" href="<?= base_url('/products') ?>">Produtos</a>
<a class="nav-link text-white" href="<?= base_url('/customers') ?>">Clientes</a>
<a class="nav-link text-white" href="<?= base_url('/finance') ?>">Financeiro</a>
<a class="nav-link text-white" href="<?= base_url('/reports') ?>">Relatórios</a>
<a class="nav-link text-white" href="<?= base_url('/users') ?>">Usuários</a>
<a class="nav-link text-white" href="<?= base_url('/settings') ?>">Configurações</a>
</nav></aside>
<main class="flex-grow-1"><header class="bg-white border-bottom p-3 d-flex justify-content-between">
<strong><?= htmlspecialchars($title ?? '') ?></strong><div><?= htmlspecialchars(Auth::user()['name'] ?? '') ?> (<?= htmlspecialchars(Auth::user()['role'] ?? '') ?>) <a class="btn btn-sm btn-danger" href="<?= base_url('/logout') ?>">Sair</a></div>
</header><section class="p-4"><?php foreach(get_flashes() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= htmlspecialchars($f['message']) ?></div><?php endforeach; ?><?= $content ?></section></main>
</div>
<script src="<?= base_url('/assets/js/app.js') ?>"></script>
</body></html>
