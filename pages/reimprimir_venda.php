<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT v.*, c.nome cliente, u.nome usuario FROM vendas v LEFT JOIN clientes c ON c.id=v.cliente_id LEFT JOIN usuarios u ON u.id=v.usuario_id WHERE v.id = ?");
$stmt->execute([$id]);
$venda = $stmt->fetch();

if (!$venda) {
    exit('Venda não encontrada.');
}

$it = db()->prepare('SELECT iv.*, p.nome produto, p.categoria FROM itens_venda iv JOIN produtos p ON p.id=iv.produto_id WHERE iv.venda_id = ?');
$it->execute([$id]);
$itens = $it->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reimpressão Venda #<?= (int) $venda['id'] ?></title>
  <style>
    body{font-family:Arial,sans-serif;max-width:720px;margin:20px auto}
    table{width:100%;border-collapse:collapse}
    td,th{border-bottom:1px solid #ddd;padding:6px;text-align:left}
  </style>
</head>
<body>
  <h2>Comprovante de Venda #<?= (int) $venda['id'] ?></h2>
  <p><strong>Data:</strong> <?= e($venda['created_at']) ?></p>
  <p><strong>Cliente:</strong> <?= e($venda['cliente'] ?? 'Não cadastrado') ?></p>
  <p><strong>Vendedor:</strong> <?= e($venda['usuario']) ?></p>
  <p><strong>Pagamento:</strong> <?= e($venda['forma_pagamento']) ?></p>
  <p><strong>Recebimento:</strong> <?= e($venda['recebimento_status'] === 'na_entrega' ? 'Receber na entrega' : 'Já recebeu') ?></p>

  <table>
    <thead><tr><th>Produto</th><th>Categoria</th><th>Validade galão</th><th>Qtd</th><th>Preço</th><th>Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($itens as $item): ?>
      <tr>
        <td><?= e($item['produto']) ?></td>
        <td><?= e($item['categoria']) ?></td>
        <td><?= e($item['validade_galao'] ?: '-') ?></td>
        <td><?= (int) $item['quantidade'] ?></td>
        <td><?= money((float) $item['preco_unitario']) ?></td>
        <td><?= money(((float) $item['preco_unitario'] * (int) $item['quantidade']) - (float) $item['desconto']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h3>Total: <?= money((float) $venda['total']) ?></h3>
  <script>window.print();</script>
</body>
</html>
