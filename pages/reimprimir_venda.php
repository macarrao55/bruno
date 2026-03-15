<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$hasReceb = tableHasColumn('vendas', 'recebimento_status');
$hasCepCliente = tableHasColumn('clientes', 'cep');
$cepField = $hasCepCliente ? 'c.cep' : 'NULL AS cep';

$sql = $hasReceb
    ? "SELECT v.*, c.nome cliente, c.telefone, {$cepField}, c.rua, c.numero, c.bairro, c.referencia, u.nome usuario
       FROM vendas v
       LEFT JOIN clientes c ON c.id = v.cliente_id
       LEFT JOIN usuarios u ON u.id = v.usuario_id
       WHERE v.id = ?"
    : "SELECT v.*, 'recebido' AS recebimento_status, c.nome cliente, c.telefone, {$cepField}, c.rua, c.numero, c.bairro, c.referencia, u.nome usuario
       FROM vendas v
       LEFT JOIN clientes c ON c.id = v.cliente_id
       LEFT JOIN usuarios u ON u.id = v.usuario_id
       WHERE v.id = ?";

$stmt = db()->prepare($sql);
$stmt->execute([$id]);
$venda = $stmt->fetch();

if (!$venda) {
    exit('Venda não encontrada.');
}

$hasValidadeGalao = tableHasColumn('itens_venda', 'validade_galao');
$hasObsItem = tableHasColumn('itens_venda', 'observacao');

$validadeField = $hasValidadeGalao ? 'iv.validade_galao' : 'NULL AS validade_galao';
$obsField = $hasObsItem ? 'iv.observacao' : 'NULL AS observacao';

$itensSql = "SELECT iv.*, {$validadeField}, {$obsField}, p.nome produto, p.categoria
             FROM itens_venda iv
             JOIN produtos p ON p.id = iv.produto_id
             WHERE iv.venda_id = ?";

$it = db()->prepare($itensSql);
$it->execute([$id]);
$itens = $it->fetchAll();

$endereco = trim(implode(' - ', array_filter([
    trim((string) ($venda['rua'] ?? '')) . (empty($venda['numero']) ? '' : ', ' . $venda['numero']),
    $venda['bairro'] ?? '',
    $venda['referencia'] ?? '',
    empty($venda['cep']) ? '' : 'CEP: ' . $venda['cep'],
])));
if ($endereco === '') {
    $endereco = 'Não informado';
}

$formaNorm = strtolower((string) ($venda['forma_pagamento'] ?? ''));
$recebimentoLabel = ($formaNorm === 'crediário' || $formaNorm === 'crediario')
    ? 'Crediário'
    : ((($venda['recebimento_status'] ?? 'recebido') === 'na_entrega') ? 'Receber na entrega' : 'Já recebeu');
$isCrediario = mb_strtolower((string) ($venda['forma_pagamento'] ?? ''), 'UTF-8') === 'crediário'
    || mb_strtolower((string) ($venda['forma_pagamento'] ?? ''), 'UTF-8') === 'crediario';

$printEmpresaNome = getSetting('print_empresa_nome', PRINT_EMPRESA_NOME);
$printEmpresaTelefone = getSetting('print_empresa_telefone', PRINT_EMPRESA_TELEFONE);
$printEmpresaInstagram = getSetting('print_empresa_instagram', PRINT_EMPRESA_INSTAGRAM);
$printRodapeTexto = getSetting('print_rodape_texto', PRINT_RODAPE_TEXTO);
$printCrediarioSegundaVia = getSettingBool('print_crediario_segunda_via', PRINT_CREDIARIO_SEGUNDA_VIA);
$crediarioDiasVencimento = max(1, (int) getSetting('crediario_dias_vencimento', '30'));
$credVencimento = '';
if ($isCrediario) {
    $baseDate = date_create((string) ($venda['created_at'] ?? 'now'));
    if ($baseDate !== false) {
        $baseDate->modify('+' . $crediarioDiasVencimento . ' day');
        $credVencimento = $baseDate->format('d/m/Y');
    }
}

$vias = [['titulo' => 'Via da empresa', 'assinatura' => false]];
if ($isCrediario && $printCrediarioSegundaVia) {
    $vias[] = ['titulo' => 'Via do cliente', 'assinatura' => true];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comprovante #<?= (int) $venda['id'] ?></title>
  <style>
    @page { size: 80mm auto; margin: 4mm; }
    body{font-family:Arial,Helvetica,sans-serif;background:#fff;color:#000;margin:0;padding:0}
    .cupom{width:72mm; margin:0 auto 4mm auto; font-size:11px; line-height:1.3; word-break:break-word}
    .center{text-align:center}
    .empresa{font-size:16px;font-weight:700}
    .hr{border-top:1px dashed #000;margin:6px 0}
    .muted{font-size:10px}
    .row{display:flex;justify-content:space-between;gap:6px}
    .row > div:last-child{text-align:right;white-space:nowrap}
    table{width:100%;border-collapse:collapse;table-layout:fixed}
    th,td{padding:2px 0;vertical-align:top;word-break:break-word}
    th{font-size:10px;text-align:left;border-bottom:1px solid #000}
    .col-prod{width:43%}
    .col-qtd{width:10%;text-align:center}
    .col-vu{width:22%;text-align:right}
    .col-sub{width:25%;text-align:right}
    .obs{font-size:10px;margin-top:1px}
    .assinatura{margin-top:16px;padding-top:12px;border-top:1px solid #000;text-align:center}
    .nao-imprimir{display:block}
    @media print {
      .nao-imprimir{display:none!important}
      .cupom{margin:0 auto 2mm auto}
      .quebra{page-break-after:always}
      .quebra:last-child{page-break-after:auto}
    }
  </style>
</head>
<body>
  <div class="nao-imprimir center" style="margin:8px 0;">
    <button onclick="window.print()">Imprimir</button>
  </div>

  <?php foreach ($vias as $idx => $via): ?>
    <section class="cupom <?= $idx < count($vias) - 1 ? 'quebra' : '' ?>">
      <div class="center empresa"><?= e($printEmpresaNome) ?></div>
      <div class="center">Tel: <?= e($printEmpresaTelefone) ?></div>
      <div class="center">Instagram: <?= e($printEmpresaInstagram) ?></div>
      <div class="hr"></div>

      <div class="row"><div>Data/Hora:</div><div><?= e($venda['created_at']) ?></div></div>
      <div class="row"><div>Venda:</div><div>#<?= (int) $venda['id'] ?></div></div>

      <div class="hr"></div>
      <div><strong>Cliente:</strong> <?= e($venda['cliente'] ?? 'Não cadastrado') ?></div>
      <div><strong>Endereço:</strong> <?= e($endereco) ?></div>

      <div class="hr"></div>
      <table>
        <thead>
          <tr>
            <th class="col-prod">Produto</th>
            <th class="col-qtd">Qtd</th>
            <th class="col-vu">Vlr un.</th>
            <th class="col-sub">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($itens as $item): ?>
            <?php $sub = ((float) $item['preco_unitario'] * (int) $item['quantidade']) - (float) ($item['desconto'] ?? 0); ?>
            <tr>
              <td class="col-prod">
                <?= e($item['produto']) ?>
                <?php if (!empty($item['observacao'])): ?><div class="obs">Obs: <?= e($item['observacao']) ?></div><?php endif; ?>
                <?php if (!empty($item['validade_galao'])): ?><div class="obs">Validade: <strong><?= e(date('m/Y', strtotime($item['validade_galao']))) ?></strong></div><?php endif; ?>
              </td>
              <td class="col-qtd"><?= (int) $item['quantidade'] ?></td>
              <td class="col-vu"><?= money((float) $item['preco_unitario']) ?></td>
              <td class="col-sub"><?= money($sub) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="hr"></div>
      <div class="row"><div><strong>Pagamento:</strong></div><div><?= e($venda['forma_pagamento']) ?></div></div>
      <div class="row"><div><strong>Recebimento:</strong></div><div><?= e($recebimentoLabel) ?></div></div>
      <div class="row" style="font-size:14px;font-weight:700"><div>TOTAL:</div><div><?= money((float) $venda['total']) ?></div></div>
      <?php if ($isCrediario && !$via['assinatura'] && $credVencimento !== ''): ?>
        <div class="row"><div><strong>Vencimento:</strong></div><div><strong><?= e($credVencimento) ?></strong></div></div>
      <?php endif; ?>

      <?php if ($via['assinatura']): ?>
        <div class="assinatura">Assinatura do Cliente: __________________________</div>
      <?php endif; ?>

      <div class="hr"></div>
      <div class="center muted"><?= e($via['titulo']) ?></div>
      <div class="center muted"><?= e($printRodapeTexto) ?></div>
    </section>
  <?php endforeach; ?>
</body>
</html>
