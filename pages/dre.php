<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$db = db();
$hasEntregas = tableExists('entregas') && tableHasColumn('entregas', 'comissao');

/**
 * Calcula indicadores DRE para um período (inclusive).
 */
function drePeriodo(PDO $db, string $inicio, string $fim, bool $hasEntregas): array
{
    $fatStmt = $db->prepare('SELECT COALESCE(SUM(total),0) v FROM vendas WHERE DATE(created_at) BETWEEN ? AND ?');
    $fatStmt->execute([$inicio, $fim]);
    $faturamento = (float) (($fatStmt->fetch()['v'] ?? 0));

    $custoStmt = $db->prepare('SELECT COALESCE(SUM(custo_unitario * quantidade),0) v FROM compras WHERE DATE(created_at) BETWEEN ? AND ?');
    $custoStmt->execute([$inicio, $fim]);
    $custos = (float) (($custoStmt->fetch()['v'] ?? 0));

    $despStmt = $db->prepare('SELECT COALESCE(SUM(valor),0) v FROM despesas WHERE data BETWEEN ? AND ?');
    $despStmt->execute([$inicio, $fim]);
    $despesas = (float) (($despStmt->fetch()['v'] ?? 0));

    $comissoes = 0.0;
    if ($hasEntregas) {
        $comStmt = $db->prepare('SELECT COALESCE(SUM(comissao),0) v FROM entregas WHERE DATE(created_at) BETWEEN ? AND ?');
        $comStmt->execute([$inicio, $fim]);
        $comissoes = (float) (($comStmt->fetch()['v'] ?? 0));
    }

    $lucroBruto = $faturamento - $custos;
    $lucroLiquido = $lucroBruto - $despesas - $comissoes;
    $margem = $faturamento > 0 ? ($lucroLiquido / $faturamento) * 100 : 0;

    return [
        'faturamento' => $faturamento,
        'custos' => $custos,
        'lucro_bruto' => $lucroBruto,
        'despesas' => $despesas,
        'comissoes' => $comissoes,
        'lucro_liquido' => $lucroLiquido,
        'margem' => $margem,
    ];
}

function variacaoPercent(float $atual, float $anterior): float
{
    if ($anterior == 0.0) {
        return $atual == 0.0 ? 0.0 : 100.0;
    }
    return (($atual - $anterior) / abs($anterior)) * 100;
}

$hoje = new DateTimeImmutable('today');
$anoAtual = (int) $hoje->format('Y');
$mesAtual = (int) $hoje->format('m');

$inicioMesAtual = $hoje->modify('first day of this month');
$fimMesAtual = $hoje->modify('last day of this month');
$inicioMesAnterior = $hoje->modify('first day of last month');
$fimMesAnterior = $hoje->modify('last day of last month');

$trimestreAtual = (int) ceil($mesAtual / 3);
$mesInicioTri = (($trimestreAtual - 1) * 3) + 1;
$inicioTrimestreAtual = new DateTimeImmutable(sprintf('%d-%02d-01', $anoAtual, $mesInicioTri));
$fimTrimestreAtual = $inicioTrimestreAtual->modify('+3 months -1 day');
$inicioTrimestreAnterior = $inicioTrimestreAtual->modify('-3 months');
$fimTrimestreAnterior = $inicioTrimestreAtual->modify('-1 day');

$inicioAnoAtual = new DateTimeImmutable(sprintf('%d-01-01', $anoAtual));
$fimAnoAtual = new DateTimeImmutable(sprintf('%d-12-31', $anoAtual));
$inicioAnoAnterior = new DateTimeImmutable(sprintf('%d-01-01', $anoAtual - 1));
$fimAnoAnterior = new DateTimeImmutable(sprintf('%d-12-31', $anoAtual - 1));

$mensalAtual = drePeriodo($db, $inicioMesAtual->format('Y-m-d'), $fimMesAtual->format('Y-m-d'), $hasEntregas);
$mensalAnterior = drePeriodo($db, $inicioMesAnterior->format('Y-m-d'), $fimMesAnterior->format('Y-m-d'), $hasEntregas);
$trimestralAtual = drePeriodo($db, $inicioTrimestreAtual->format('Y-m-d'), $fimTrimestreAtual->format('Y-m-d'), $hasEntregas);
$trimestralAnterior = drePeriodo($db, $inicioTrimestreAnterior->format('Y-m-d'), $fimTrimestreAnterior->format('Y-m-d'), $hasEntregas);
$anualAtual = drePeriodo($db, $inicioAnoAtual->format('Y-m-d'), $fimAnoAtual->format('Y-m-d'), $hasEntregas);
$anualAnterior = drePeriodo($db, $inicioAnoAnterior->format('Y-m-d'), $fimAnoAnterior->format('Y-m-d'), $hasEntregas);

$historicoMensal = [];
$cursor = $inicioMesAtual;
for ($i = 0; $i < 6; $i++) {
    $ini = $cursor->modify("-{$i} months")->modify('first day of this month');
    $fim = $ini->modify('last day of this month');
    $historicoMensal[] = [
        'periodo' => $ini->format('m/Y'),
        'dados' => drePeriodo($db, $ini->format('Y-m-d'), $fim->format('Y-m-d'), $hasEntregas),
    ];
}

renderHeader('DRE Detalhado');
?>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card border-start border-4 border-primary"><div class="card-body">
      <small class="text-muted d-block">Mensal (<?= e($inicioMesAtual->format('m/Y')) ?>)</small>
      <h6 class="mb-1">Lucro líquido: <?= money($mensalAtual['lucro_liquido']) ?></h6>
      <small>vs mês anterior: <?= number_format(variacaoPercent($mensalAtual['lucro_liquido'], $mensalAnterior['lucro_liquido']), 2, ',', '.') ?>%</small>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card border-start border-4 border-warning"><div class="card-body">
      <small class="text-muted d-block">Trimestral (T<?= $trimestreAtual ?>/<?= $anoAtual ?>)</small>
      <h6 class="mb-1">Lucro líquido: <?= money($trimestralAtual['lucro_liquido']) ?></h6>
      <small>vs trimestre anterior: <?= number_format(variacaoPercent($trimestralAtual['lucro_liquido'], $trimestralAnterior['lucro_liquido']), 2, ',', '.') ?>%</small>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card border-start border-4 border-success"><div class="card-body">
      <small class="text-muted d-block">Anual (<?= $anoAtual ?>)</small>
      <h6 class="mb-1">Lucro líquido: <?= money($anualAtual['lucro_liquido']) ?></h6>
      <small>vs ano anterior: <?= number_format(variacaoPercent($anualAtual['lucro_liquido'], $anualAnterior['lucro_liquido']), 2, ',', '.') ?>%</small>
    </div></div>
  </div>
</div>

<div class="card mb-3"><div class="card-body table-responsive">
  <h6>Comparação Mensal, Trimestral e Anual</h6>
  <table class="table table-bordered align-middle">
    <thead>
      <tr>
        <th>Indicador</th>
        <th>Mensal Atual</th>
        <th>Mês Anterior</th>
        <th>Trimestre Atual</th>
        <th>Trimestre Anterior</th>
        <th>Ano Atual</th>
        <th>Ano Anterior</th>
      </tr>
    </thead>
    <tbody>
      <tr><th>Faturamento</th><td><?= money($mensalAtual['faturamento']) ?></td><td><?= money($mensalAnterior['faturamento']) ?></td><td><?= money($trimestralAtual['faturamento']) ?></td><td><?= money($trimestralAnterior['faturamento']) ?></td><td><?= money($anualAtual['faturamento']) ?></td><td><?= money($anualAnterior['faturamento']) ?></td></tr>
      <tr><th>Custos</th><td><?= money($mensalAtual['custos']) ?></td><td><?= money($mensalAnterior['custos']) ?></td><td><?= money($trimestralAtual['custos']) ?></td><td><?= money($trimestralAnterior['custos']) ?></td><td><?= money($anualAtual['custos']) ?></td><td><?= money($anualAnterior['custos']) ?></td></tr>
      <tr><th>Lucro Bruto</th><td><?= money($mensalAtual['lucro_bruto']) ?></td><td><?= money($mensalAnterior['lucro_bruto']) ?></td><td><?= money($trimestralAtual['lucro_bruto']) ?></td><td><?= money($trimestralAnterior['lucro_bruto']) ?></td><td><?= money($anualAtual['lucro_bruto']) ?></td><td><?= money($anualAnterior['lucro_bruto']) ?></td></tr>
      <tr><th>Despesas</th><td><?= money($mensalAtual['despesas']) ?></td><td><?= money($mensalAnterior['despesas']) ?></td><td><?= money($trimestralAtual['despesas']) ?></td><td><?= money($trimestralAnterior['despesas']) ?></td><td><?= money($anualAtual['despesas']) ?></td><td><?= money($anualAnterior['despesas']) ?></td></tr>
      <tr><th>Comissões</th><td><?= money($mensalAtual['comissoes']) ?></td><td><?= money($mensalAnterior['comissoes']) ?></td><td><?= money($trimestralAtual['comissoes']) ?></td><td><?= money($trimestralAnterior['comissoes']) ?></td><td><?= money($anualAtual['comissoes']) ?></td><td><?= money($anualAnterior['comissoes']) ?></td></tr>
      <tr><th>Lucro Líquido</th><td><strong><?= money($mensalAtual['lucro_liquido']) ?></strong></td><td><?= money($mensalAnterior['lucro_liquido']) ?></td><td><strong><?= money($trimestralAtual['lucro_liquido']) ?></strong></td><td><?= money($trimestralAnterior['lucro_liquido']) ?></td><td><strong><?= money($anualAtual['lucro_liquido']) ?></strong></td><td><?= money($anualAnterior['lucro_liquido']) ?></td></tr>
      <tr><th>Margem</th><td><?= number_format($mensalAtual['margem'], 2, ',', '.') ?>%</td><td><?= number_format($mensalAnterior['margem'], 2, ',', '.') ?>%</td><td><?= number_format($trimestralAtual['margem'], 2, ',', '.') ?>%</td><td><?= number_format($trimestralAnterior['margem'], 2, ',', '.') ?>%</td><td><?= number_format($anualAtual['margem'], 2, ',', '.') ?>%</td><td><?= number_format($anualAnterior['margem'], 2, ',', '.') ?>%</td></tr>
    </tbody>
  </table>
</div></div>

<div class="card"><div class="card-body table-responsive">
  <h6>Histórico dos últimos 6 meses (Lucro Líquido e Margem)</h6>
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Período</th>
        <th>Faturamento</th>
        <th>Lucro Líquido</th>
        <th>Margem</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($historicoMensal as $item): ?>
        <tr>
          <td><?= e($item['periodo']) ?></td>
          <td><?= money($item['dados']['faturamento']) ?></td>
          <td><?= money($item['dados']['lucro_liquido']) ?></td>
          <td><?= number_format($item['dados']['margem'], 2, ',', '.') ?>%</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div></div>

<?php renderFooter(); ?>
