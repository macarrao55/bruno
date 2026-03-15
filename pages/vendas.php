<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$hasReceb = tableHasColumn('vendas', 'recebimento_status');
$formasPagamento = getPaymentMethods();

$fDataInicio = trim((string) ($_GET['data_inicio'] ?? ''));
$fDataFim = trim((string) ($_GET['data_fim'] ?? ''));
$fCliente = trim((string) ($_GET['cliente'] ?? ''));
$fVendedor = trim((string) ($_GET['vendedor'] ?? ''));
$fForma = trim((string) ($_GET['forma_pagamento'] ?? ''));
$fProduto = trim((string) ($_GET['produto'] ?? ''));
$fValorMin = trim((string) ($_GET['valor_min'] ?? ''));
$fValorMax = trim((string) ($_GET['valor_max'] ?? ''));

$sql = $hasReceb
    ? "SELECT v.*, c.nome cliente, u.nome usuario, GROUP_CONCAT(DISTINCT p.nome ORDER BY p.nome SEPARATOR ', ') produtos
       FROM vendas v
       LEFT JOIN clientes c ON c.id = v.cliente_id
       LEFT JOIN usuarios u ON u.id = v.usuario_id
       LEFT JOIN itens_venda iv ON iv.venda_id = v.id
       LEFT JOIN produtos p ON p.id = iv.produto_id
       WHERE 1=1"
    : "SELECT v.*, 'recebido' AS recebimento_status, c.nome cliente, u.nome usuario, GROUP_CONCAT(DISTINCT p.nome ORDER BY p.nome SEPARATOR ', ') produtos
       FROM vendas v
       LEFT JOIN clientes c ON c.id = v.cliente_id
       LEFT JOIN usuarios u ON u.id = v.usuario_id
       LEFT JOIN itens_venda iv ON iv.venda_id = v.id
       LEFT JOIN produtos p ON p.id = iv.produto_id
       WHERE 1=1";

$params = [];

if ($fDataInicio !== '') {
    $sql .= ' AND DATE(v.created_at) >= ?';
    $params[] = $fDataInicio;
}
if ($fDataFim !== '') {
    $sql .= ' AND DATE(v.created_at) <= ?';
    $params[] = $fDataFim;
}
if ($fCliente !== '') {
    $sql .= ' AND c.nome LIKE ?';
    $params[] = '%' . $fCliente . '%';
}
if ($fVendedor !== '') {
    $sql .= ' AND u.nome LIKE ?';
    $params[] = '%' . $fVendedor . '%';
}
if ($fForma !== '') {
    $sql .= ' AND v.forma_pagamento = ?';
    $params[] = $fForma;
}
if ($fProduto !== '') {
    $sql .= ' AND p.nome LIKE ?';
    $params[] = '%' . $fProduto . '%';
}
if ($fValorMin !== '' && is_numeric($fValorMin)) {
    $sql .= ' AND v.total >= ?';
    $params[] = (float) $fValorMin;
}
if ($fValorMax !== '' && is_numeric($fValorMax)) {
    $sql .= ' AND v.total <= ?';
    $params[] = (float) $fValorMax;
}

$sql .= ' GROUP BY v.id ORDER BY v.id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$vendas = $stmt->fetchAll();

renderHeader('Todas as Vendas');
?>
<div class="card mb-3"><div class="card-body">
  <form class="row g-2" method="get">
    <div class="col-md-2"><label class="form-label">Data inicial</label><input type="date" name="data_inicio" class="form-control" value="<?= e($fDataInicio) ?>"></div>
    <div class="col-md-2"><label class="form-label">Data final</label><input type="date" name="data_fim" class="form-control" value="<?= e($fDataFim) ?>"></div>
    <div class="col-md-2"><label class="form-label">Cliente</label><input type="text" name="cliente" class="form-control" value="<?= e($fCliente) ?>" placeholder="Nome do cliente"></div>
    <div class="col-md-2"><label class="form-label">Vendedor</label><input type="text" name="vendedor" class="form-control" value="<?= e($fVendedor) ?>"></div>
    <div class="col-md-2"><label class="form-label">Produto</label><input type="text" name="produto" class="form-control" value="<?= e($fProduto) ?>"></div>
    <div class="col-md-2"><label class="form-label">Pagamento</label>
      <select name="forma_pagamento" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($formasPagamento as $fp): ?>
          <option value="<?= e($fp) ?>" <?= $fForma === $fp ? 'selected' : '' ?>><?= e($fp) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><label class="form-label">Valor mínimo</label><input type="number" step="0.01" name="valor_min" class="form-control" value="<?= e($fValorMin) ?>"></div>
    <div class="col-md-2"><label class="form-label">Valor máximo</label><input type="number" step="0.01" name="valor_max" class="form-control" value="<?= e($fValorMax) ?>"></div>
    <div class="col-md-8 d-flex align-items-end gap-2">
      <button class="btn btn-primary" type="submit">Pesquisar</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/pages/vendas.php">Limpar</a>
    </div>
  </form>
</div></div>

<div class="card"><div class="card-body table-responsive">
  <table class="table table-striped">
    <thead>
      <tr>
        <th>#</th><th>Data</th><th>Cliente</th><th>Vendedor</th><th>Produtos</th><th>Pagamento</th><th>Recebimento</th><th>Total</th><th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($vendas as $v): ?>
        <tr>
          <td><?= (int) $v['id'] ?></td>
          <td><?= e($v['created_at']) ?></td>
          <td><?= e($v['cliente'] ?? 'Não cadastrado') ?></td>
          <td><?= e($v['usuario'] ?? '-') ?></td>
          <td><?= e($v['produtos'] ?? '-') ?></td>
          <td><?= e($v['forma_pagamento']) ?></td>
          <td><?= e((normalizeTextSimple((string)($v['forma_pagamento'] ?? '')) === 'crediario') ? 'Crediário' : (($v['recebimento_status'] ?? 'recebido') === 'na_entrega' ? 'Receber na entrega' : 'Já recebeu')) ?></td>
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
