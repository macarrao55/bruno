<?php if(!$order): ?>Pedido não encontrado<?php return; endif; ?>
<?php $company = 'MEGA LANCHES ERP'; ?>
<div class="center"><strong><?= $company ?></strong><br>Tel: (11)99999-9999<br>Instagram: @megalanches<br><strong><?= strtoupper($mode) ?></strong></div>
<div class="line"></div>
Pedido #<?= $order['id'] ?> - <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?><br>
Cliente: <?= htmlspecialchars($order['customer_name'] ?? 'Consumidor') ?><br>
Fone: <?= htmlspecialchars((string)($order['phone'] ?? '')) ?><br>
Endereço: <?= htmlspecialchars((string)($order['address'] ?? '')) ?><br>
Bairro: <?= htmlspecialchars((string)($order['neighborhood'] ?? '')) ?><br>
Tipo: <?= htmlspecialchars($order['order_type']) ?><br>
<div class="line"></div>
<?php foreach($order['items'] as $i): ?><?= $i['quantity'] ?>x <?= htmlspecialchars($i['product_name']) ?> ... R$ <?= number_format($i['total_price'],2,',','.') ?><br>
<?php foreach($i['addons'] as $a): ?>+ <?= htmlspecialchars($a['addon_name']) ?> (R$ <?= number_format($a['addon_price'],2,',','.') ?>)<br><?php endforeach; ?>
<?php endforeach; ?>
<div class="line"></div>
Pgto: <?= htmlspecialchars($order['payment_method']) ?><br>
Desconto: R$ <?= number_format($order['discount_amount'],2,',','.') ?><br>
Taxa entrega: R$ <?= number_format($order['delivery_fee'],2,',','.') ?><br>
TOTAL: <strong>R$ <?= number_format($order['total_amount'],2,',','.') ?></strong><br>
<?php if($order['payment_method']==='crediario'): ?><br>ASSINATURA: __________________________<?php endif; ?>
<script>window.print()</script>
