<div class="row g-3 mb-3">
    <div class="col">
        <div class="card"><div class="card-body"><small>Vendas hoje</small><h4>R$ <?= number_format($stats['today'],2,',','.') ?></h4></div></div>
    </div>
    <div class="col">
        <div class="card"><div class="card-body"><small>Pedidos hoje</small><h4><?= (int)$stats['orders'] ?></h4></div></div>
    </div>
    <div class="col">
        <div class="card"><div class="card-body"><small>Ticket médio</small><h4>R$ <?= number_format($stats['ticket'],2,',','.') ?></h4></div></div>
    </div>
    <div class="col">
        <div class="card"><div class="card-body"><small>Contas a receber</small><h4>R$ <?= number_format($stats['receivable'],2,',','.') ?></h4></div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Média de tempo dos pedidos (venda → preparo / entrega)</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Média até preparo</th>
                    <th>Média até entrega</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $periodLabels = [
                    'day' => 'Hoje',
                    'month' => 'Mês atual',
                    'year' => 'Ano atual',
                ];
                foreach ($periodLabels as $periodKey => $label):
                    $prep = $stats['timeAverages'][$periodKey]['prep'] ?? null;
                    $delivery = $stats['timeAverages'][$periodKey]['delivery'] ?? null;
                ?>
                    <tr>
                        <td><?= $label ?></td>
                        <td><?= $prep !== null ? number_format((float)$prep, 1, ',', '.') . ' min' : '-' ?></td>
                        <td><?= $delivery !== null ? number_format((float)$delivery, 1, ',', '.') . ' min' : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Formas de pagamento (hoje)</div>
            <ul class="list-group list-group-flush">
                <?php foreach($stats['pay'] as $p): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= htmlspecialchars($p['payment_method']) ?></span>
                        <strong>R$ <?= number_format($p['total'],2,',','.') ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Top produtos 30 dias</div>
            <ul class="list-group list-group-flush">
                <?php foreach($stats['topProducts'] as $p): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= htmlspecialchars($p['product_name']) ?></span>
                        <span><?= (float)$p['qty'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Top bairros 30 dias</div>
            <ul class="list-group list-group-flush">
                <?php foreach($stats['topBairro'] as $b): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= htmlspecialchars((string)$b['neighborhood']) ?></span>
                        <span><?= (int)$b['qty'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<p class="mt-3">Caixa: <span class="badge <?= $stats['cashOpen'] ? 'bg-success' : 'bg-danger' ?>"><?= $stats['cashOpen'] ? 'Aberto' : 'Fechado' ?></span> | Clientes novos hoje: <strong><?= (int)$stats['newCustomers'] ?></strong></p>
