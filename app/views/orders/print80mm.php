<?php if(!$order): ?>Pedido não encontrado<?php return; endif; ?>
<div class="center"><strong>MEGA LANCHES ERP</strong><br>Tel: (11) 99999-9999<br>Instagram: @megalanches</div>
<div class="line"></div>
Pedido #<?= $order['id'] ?><br>
Data: <?= date('d/m/Y H:i') ?><br>
Cliente: <?= htmlspecialchars($order['customer_name'] ?? 'Consumidor') ?><br>
Tipo: <?= htmlspecialchars($order['order_type']) ?><br>
Pagamento: <?= htmlspecialchars($order['payment_method']) ?><br>
<div class="line"></div>
TOTAL: R$ <?= number_format((float)$order['total_amount'],2,',','.') ?><br>
Desconto: R$ <?= number_format((float)$order['discount_amount'],2,',','.') ?><br>
Taxa Entrega: R$ <?= number_format((float)$order['delivery_fee'],2,',','.') ?><br>
<div class="line"></div>
<div class="center">Obrigado pela preferência!</div>
<script>window.print()</script>
