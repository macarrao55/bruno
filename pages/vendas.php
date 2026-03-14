<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$hasReceb = tableHasColumn('vendas', 'recebimento_status');
$sql = $hasReceb
    ? "SELECT v.*, c.nome cliente, u.nome usuario FROM vendas v LEFT JOIN clientes c ON c.id=v.cliente_id LEFT JOIN usuarios u ON u.id=v.usuario_id ORDER BY v.id DESC"
    : "SELECT v.*, 'recebido' AS recebimento_status, c.nome cliente, u.nome usuario FROM vendas v LEFT JOIN clientes c ON c.id=v.cliente_id LEFT JOIN usuarios u ON u.id=v.usuario_id ORDER BY v.id DESC";

$vendas = db()->query($sql)->fetchAll();
renderHeader('Todas as Vendas');
?>
<div class="card"><div class="card-body table-responsive">
  <table class="table table-striped">
    <thead>
      <tr>
        <th>#</th><th>Data</th><th>Cliente</th><th>Vendedor</th><th>Pagamento</th><th>Recebimento</th><th>Total</th><th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($vendas as $v): ?>
        <tr>
          <td><?= (int) $v['id'] ?></td>
          <td><?= e($v['created_at']) ?></td>
          <td><?= e($v['cliente'] ?? 'Não cadastrado') ?></td>
          <td><?= e($v['usuario'] ?? '-') ?></td>
          <td><?= e($v['forma_pagamento']) ?></td>
          <td><?= e((strtolower((string)($v['forma_pagamento'] ?? '')) === 'crediário' || strtolower((string)($v['forma_pagamento'] ?? '')) === 'crediario') ? 'Crediário' : (($v['recebimento_status'] ?? 'recebido') === 'na_entrega' ? 'Receber na entrega' : 'Já recebeu')) ?></td>
          <td><?= money((float) $v['total']) ?></td>
          <td>
            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= BASE_URL ?>/pages/reimprimir_venda.php?id=<?= (int) $v['id'] ?>">Reimprimir</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div></div>
<?php renderFooter(); ?>
