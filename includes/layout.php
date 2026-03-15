<?php
require_once __DIR__ . '/auth.php';

function renderHeader(string $title): void
{
    $user = currentUser();
    $appNome = getSetting('app_nome', APP_NAME);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title><?= e($title) ?> - <?= e($appNome) ?></title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      <style>
        body{background:#f4f6f9}
        .sidebar{min-height:100vh;background:#1f2937}
        .sidebar a{color:#d1d5db;text-decoration:none;display:block;padding:10px 14px;border-radius:8px}
        .sidebar a:hover,.sidebar a.active{background:#374151;color:#fff}
        .card-kpi{border:0;border-radius:16px}
      </style>
    </head>
    <body>
    <div class="container-fluid">
      <div class="row">
        <aside class="col-md-2 sidebar p-3">
          <h5 class="text-white mb-3"><?= e($appNome) ?></h5>
          <a href="<?= BASE_URL ?>/index.php">Dashboard</a>
          <a href="<?= BASE_URL ?>/pages/pdv.php">PDV</a>
          <a href="<?= BASE_URL ?>/pages/vendas.php">Vendas</a>
          <a href="<?= BASE_URL ?>/pages/clientes.php">Clientes</a>
          <a href="<?= BASE_URL ?>/pages/produtos.php">Produtos</a>
          <a href="<?= BASE_URL ?>/pages/compras.php">Compras</a>
          <a href="<?= BASE_URL ?>/pages/estoque.php">Estoque</a>
          <a href="<?= BASE_URL ?>/pages/financeiro.php">Financeiro</a>
          <a href="<?= BASE_URL ?>/pages/contas_receber.php">Contas a Receber</a>
          <a href="<?= BASE_URL ?>/pages/vasilhames.php">Galões/Botijões</a>
          <a href="<?= BASE_URL ?>/pages/dre.php">DRE</a>
          <a href="<?= BASE_URL ?>/pages/relatorios.php">Relatórios</a>
          <?php if (hasRole(['administrador', 'gerente'])): ?>
            <a href="<?= BASE_URL ?>/pages/configuracoes.php">Configurações</a>
          <?php endif; ?>
        </aside>
        <main class="col-md-10 p-4">
          <nav class="navbar navbar-light bg-white shadow-sm rounded-3 px-3 mb-4">
            <span class="navbar-brand mb-0 h6"><?= e($title) ?></span>
            <div>
              <span class="me-2"><?= e($user['nome'] ?? 'Visitante') ?></span>
              <span class="badge bg-secondary me-3"><?= e(roleLabel($user['nivel'] ?? '')) ?></span>
              <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/logout.php">Sair</a>
            </div>
          </nav>
    <?php
}

function renderFooter(): void
{
    ?>
        </main>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
