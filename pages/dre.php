<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$faturamento=(float)(db()->query('SELECT COALESCE(SUM(total),0) v FROM vendas')->fetch()['v']??0);
$custos=(float)(db()->query('SELECT COALESCE(SUM(custo_unitario*quantidade),0) v FROM compras')->fetch()['v']??0);
$lucroBruto=$faturamento-$custos;
$despesas=(float)(db()->query('SELECT COALESCE(SUM(valor),0) v FROM despesas')->fetch()['v']??0);
$comissoes=(float)(db()->query('SELECT COALESCE(SUM(comissao),0) v FROM entregas')->fetch()['v']??0);
$lucroLiquido=$lucroBruto-$despesas-$comissoes;
$margem=$faturamento>0?($lucroLiquido/$faturamento)*100:0;
renderHeader('DRE Automático');
?>
<div class="card"><div class="card-body">
<table class="table table-bordered">
<tr><th>Faturamento</th><td><?= money($faturamento) ?></td></tr>
<tr><th>Custos</th><td><?= money($custos) ?></td></tr>
<tr><th>Lucro bruto</th><td><?= money($lucroBruto) ?></td></tr>
<tr><th>Despesas</th><td><?= money($despesas) ?></td></tr>
<tr><th>Comissões</th><td><?= money($comissoes) ?></td></tr>
<tr><th>Lucro líquido</th><td><?= money($lucroLiquido) ?></td></tr>
<tr><th>Margem de lucro</th><td><?= number_format($margem,2,',','.') ?>%</td></tr>
</table>
</div></div>
<?php renderFooter(); ?>
