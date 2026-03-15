<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><small>Vendas hoje</small><h3>R$ <?= number_format((float)$stats['sales_today'],2,',','.') ?></h3></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><small>Vendas mês</small><h3>R$ <?= number_format((float)$stats['sales_month'],2,',','.') ?></h3></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><small>Pedidos hoje</small><h3><?= (int)$stats['orders_today'] ?></h3></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><small>Ticket médio</small><h3>R$ <?= number_format((float)$stats['avg_ticket'],2,',','.') ?></h3></div></div></div>
</div>
<div class="row g-3">
    <div class="col-md-8"><div class="card"><div class="card-header">Vendas por período</div><div class="card-body"><canvas id="salesChart"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header">Saldo de caixa</div><div class="card-body"><h3 class="text-success">R$ <?= number_format((float)$stats['cash_balance'],2,',','.') ?></h3><p class="mb-0">Status: <span class="badge text-bg-success">Caixa aberto</span></p></div></div></div>
</div>
<script>window.dashboardSales = <?= json_encode($salesByDay, JSON_UNESCAPED_UNICODE) ?>;</script>
