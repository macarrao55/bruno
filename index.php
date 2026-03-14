<?php
require_once __DIR__ . '/includes/layout.php';
requireLogin();

$today = date('Y-m-d');
$month = date('Y-m');

$faturamentoDia = (float) (db()->query("SELECT COALESCE(SUM(total),0) AS v FROM vendas WHERE DATE(created_at) = '$today'")->fetch()['v'] ?? 0);
$faturamentoMes = (float) (db()->query("SELECT COALESCE(SUM(total),0) AS v FROM vendas WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetch()['v'] ?? 0);
$ticketMedio = (float) (db()->query("SELECT COALESCE(AVG(total),0) AS v FROM vendas WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetch()['v'] ?? 0);
$lucro = (float) (db()->query("SELECT COALESCE(SUM(lucro),0) AS v FROM vendas WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetch()['v'] ?? 0);
$estoqueBaixo = (int) (db()->query('SELECT COUNT(*) c FROM produtos WHERE estoque_atual <= estoque_minimo')->fetch()['c'] ?? 0);
$clientesDevendo = (int) (db()->query("SELECT COUNT(*) c FROM contas_receber WHERE status = 'aberto'")->fetch()['c'] ?? 0);

$salesByDay = db()->query("SELECT DATE(created_at) dia, SUM(total) total FROM vendas WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY dia")->fetchAll();
$salesByProduct = db()->query("SELECT p.nome, COALESCE(SUM(iv.quantidade),0) qtd FROM itens_venda iv JOIN produtos p ON p.id = iv.produto_id GROUP BY p.nome ORDER BY qtd DESC LIMIT 5")->fetchAll();
$salesByBairro = db()->query("SELECT c.bairro, COALESCE(SUM(v.total),0) total FROM vendas v LEFT JOIN clientes c ON c.id = v.cliente_id GROUP BY c.bairro ORDER BY total DESC LIMIT 5")->fetchAll();

renderHeader('Dashboard');
?>
<div class="row g-3 mb-4">
  <?php
  $kpis = [
    ['Faturamento Dia', money($faturamentoDia), 'success'],
    ['Faturamento Mês', money($faturamentoMes), 'primary'],
    ['Ticket Médio', money($ticketMedio), 'info'],
    ['Lucro', money($lucro), 'warning'],
    ['Estoque Baixo', (string) $estoqueBaixo, 'danger'],
    ['Clientes Devendo', (string) $clientesDevendo, 'secondary'],
  ];
  foreach ($kpis as [$label, $value, $color]): ?>
    <div class="col-md-3">
      <div class="card card-kpi shadow-sm border-start border-4 border-<?= $color ?>">
        <div class="card-body"><small class="text-muted"><?= e($label) ?></small><h5><?= e($value) ?></h5></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-md-4"><div class="card"><div class="card-body"><canvas id="dayChart"></canvas></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body"><canvas id="productChart"></canvas></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body"><canvas id="bairroChart"></canvas></div></div></div>
</div>
<script>
const dayData = <?= json_encode($salesByDay) ?>;
const productData = <?= json_encode($salesByProduct) ?>;
const bairroData = <?= json_encode($salesByBairro) ?>;
new Chart(document.getElementById('dayChart'), {type:'line', data:{labels:dayData.map(x=>x.dia), datasets:[{label:'Vendas por dia', data:dayData.map(x=>x.total)}]}});
new Chart(document.getElementById('productChart'), {type:'bar', data:{labels:productData.map(x=>x.nome), datasets:[{label:'Vendas por produto', data:productData.map(x=>x.qtd)}]}});
new Chart(document.getElementById('bairroChart'), {type:'pie', data:{labels:bairroData.map(x=>x.bairro ?? 'Sem bairro'), datasets:[{label:'Vendas por bairro', data:bairroData.map(x=>x.total)}]}});
</script>
<?php renderFooter(); ?>
