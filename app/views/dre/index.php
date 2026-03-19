<?php
$dre = $dre ?? [];
$gross = (float)($dre['gross'] ?? 0);
$discounts = (float)($dre['discounts'] ?? 0);
$net = (float)($dre['net'] ?? 0);
$cmv = (float)($dre['cmv'] ?? 0);
$lucroBruto = (float)($dre['lucroBruto'] ?? 0);
$despesas = (float)($dre['despesas'] ?? 0);
$lucroLiquido = (float)($dre['lucroLiquido'] ?? 0);
$margemBruta = $net > 0 ? ($lucroBruto / $net) * 100 : 0;
$margemLiquida = $net > 0 ? ($lucroLiquido / $net) * 100 : 0;
?>

<div class="card mb-3">
    <div class="card-header">Filtro do DRE</div>
    <div class="card-body">
        <form method="get" action="<?= base_url('/dre') ?>" class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Data inicial</label>
                <input type="date" name="start" class="form-control" value="<?= htmlspecialchars((string)($start ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Data final</label>
                <input type="date" name="end" class="form-control" value="<?= htmlspecialchars((string)($end ?? '')) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100">Atualizar DRE</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">DRE Gerencial</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Receita Bruta</span>
                    <strong>R$ <?= number_format($gross, 2, ',', '.') ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>(-) Descontos</span>
                    <strong>R$ <?= number_format($discounts, 2, ',', '.') ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>= Receita Líquida</span>
                    <strong>R$ <?= number_format($net, 2, ',', '.') ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>(-) CMV</span>
                    <strong>R$ <?= number_format($cmv, 2, ',', '.') ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>= Lucro Bruto</span>
                    <strong>R$ <?= number_format($lucroBruto, 2, ',', '.') ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>(-) Despesas Operacionais</span>
                    <strong>R$ <?= number_format($despesas, 2, ',', '.') ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>= Lucro Líquido</span>
                    <strong class="<?= $lucroLiquido >= 0 ? 'text-success' : 'text-danger' ?>">R$ <?= number_format($lucroLiquido, 2, ',', '.') ?></strong>
                </li>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">Indicadores</div>
            <div class="card-body">
                <p class="mb-2">Margem Bruta: <strong><?= number_format($margemBruta, 2, ',', '.') ?>%</strong></p>
                <p class="mb-2">Margem Líquida: <strong><?= number_format($margemLiquida, 2, ',', '.') ?>%</strong></p>
                <p class="mb-0 text-muted">Período analisado: <?= htmlspecialchars((string)($start ?? '')) ?> até <?= htmlspecialchars((string)($end ?? '')) ?></p>
            </div>
        </div>
        <div class="alert alert-info mb-0">
            O DRE está integrado às vendas, descontos, CMV por itens e despesas financeiras registradas no sistema.
        </div>
    </div>
</div>
