<?php if(!$openCash): ?><div class="alert alert-warning">Caixa fechado. Abra o caixa para vender.</div><?php endif; ?>
<div class="row g-3">
<div class="col-md-8">
<div class="d-flex gap-2 mb-2"><input id="productSearch" class="form-control" placeholder="Buscar produto"><select id="categoryFilter" class="form-select" style="max-width:220px"><option value="">Todas</option><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="row g-2"><?php foreach($products as $p): ?><div class="col-md-4 pdv-item" data-name="<?= strtolower($p['name']) ?>" data-category="<?= $p['category_id'] ?>"><button class="btn btn-light border w-100 pdv-product" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-price="<?= $p['price'] ?>" data-cost="<?= $p['cost'] ?>" data-controls-stock="<?= $p['controls_stock'] ?>" data-allows-addons="<?= $p['allows_addons'] ?>"><?= htmlspecialchars($p['name']) ?><br><strong>R$ <?= number_format($p['price'],2,',','.') ?></strong></button></div><?php endforeach; ?></div>
</div>
<div class="col-md-4"><div class="card"><div class="card-header">Carrinho</div><div class="card-body">
<form id="checkoutForm" action="<?= base_url('/pdv/checkout') ?>" method="post"><?= csrf_field() ?>
<div id="cartItems"></div>
<input type="hidden" id="itemsJson" name="items_json"><input type="hidden" name="subtotal" id="subtotalInput"><input type="hidden" name="total_amount" id="totalInput"><input type="hidden" name="change_amount" id="changeAmount">
<label>Cliente</label><select name="customer_id" class="form-select mb-2"><option value="">Consumidor final</option><?php foreach($customers as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> - <?= htmlspecialchars($c['phone']) ?></option><?php endforeach; ?></select>
<label>Tipo pedido</label><select name="order_type" class="form-select mb-2"><option value="balcao">Balcão</option><option value="delivery">Delivery</option><option value="retirada">Retirada</option></select>
<label>Pagamento</label><select name="payment_method" class="form-select mb-2" id="paymentMethod"><option value="dinheiro">Dinheiro</option><option value="pix">Pix</option><option value="debito">Débito</option><option value="credito">Crédito</option><option value="crediario">Crediário</option></select>
<div class="row g-2 mb-2"><div class="col"><input id="discount" name="discount_amount" type="number" step="0.01" value="0" class="form-control" placeholder="Desconto"></div><div class="col"><input id="deliveryFee" name="delivery_fee" type="number" step="0.01" value="0" class="form-control" placeholder="Taxa entrega"></div></div>
<div class="mb-2"><input id="amountReceived" type="number" step="0.01" class="form-control" placeholder="Valor recebido (dinheiro)"></div>
<textarea class="form-control mb-2" name="notes" placeholder="Observações"></textarea>
<h5>Total: <span id="cartTotal">R$ 0,00</span></h5><small>Troco: <span id="changeText">R$ 0,00</span></small>
<button class="btn btn-success w-100 mt-2" <?= !$openCash ? 'disabled' : '' ?>>Finalizar venda</button>
</form>
</div></div></div></div>
