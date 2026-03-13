<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
renderHeader('Relatórios');
?>
<div class="card"><div class="card-body">
  <p>Gerar relatórios de vendas, estoque, financeiro, clientes, produtos e entregas (com opção de reimpressão na tela de vendas).</p>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/exports/export.php?tipo=vendas">Vendas (Excel/CSV)</a>
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/exports/export.php?tipo=estoque">Estoque (Excel/CSV)</a>
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/exports/export.php?tipo=financeiro">Financeiro (Excel/CSV)</a>
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/exports/export.php?tipo=clientes">Clientes (Excel/CSV)</a>
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/exports/export.php?tipo=produtos">Produtos (Excel/CSV)</a>
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/exports/export.php?tipo=entregas">Entregas (Excel/CSV)</a>
  </div>
  <small class="text-muted d-block mt-2">PDF pode ser adicionado com biblioteca Dompdf futuramente.</small>
</div></div>
<?php renderFooter(); ?>
