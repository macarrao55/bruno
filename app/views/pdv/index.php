<div class="row g-3">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <div class="d-flex gap-2 mb-3"><input id="productSearch" class="form-control" placeholder="Buscar produto..."><select id="categoryFilter" class="form-select" style="max-width:250px"><option value="">Todas categorias</option><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="row g-2" id="pdvGrid"><?php foreach($products as $p): ?><div class="col-md-4 pdv-item" data-name="<?= strtolower($p['name']) ?>" data-category="<?= $p['category_id'] ?>"><button class="btn btn-light border w-100 text-start pdv-product" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-price="<?= $p['sale_price'] ?>"><strong><?= htmlspecialchars($p['name']) ?></strong><br><span class="text-success">R$ <?= number_format((float)$p['sale_price'],2,',','.') ?></span></button></div><?php endforeach; ?></div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header">Carrinho</div><div class="card-body">
            <form id="checkoutForm" method="post" action="<?= base_url('/pdv/checkout') ?>">
                <?= csrf_field() ?>
                <div id="cartItems" class="small"></div>
                <input type="hidden" name="items_json" id="itemsJson">
                <div class="mb-2"><label>Cliente</label><select name="customer_id" class="form-select"><option value="">Consumidor</option><?php foreach($customers as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> - <?= htmlspecialchars($c['phone_main']) ?></option><?php endforeach; ?></select></div>
                <div class="mb-2"><label>Tipo</label><select name="order_type" class="form-select"><option value="balcao">Balcão</option><option value="delivery">Delivery</option><option value="retirada">Retirada</option></select></div>
                <div class="mb-2"><label>Pagamento</label><select name="payment_method" class="form-select"><option>dinheiro</option><option>pix</option><option>debito</option><option>credito</option><option>crediario</option></select></div>
                <div class="row g-2 mb-2"><div class="col"><input name="discount_amount" id="discount" class="form-control" type="number" step="0.01" value="0" placeholder="Desconto"></div><div class="col"><input name="delivery_fee" id="deliveryFee" class="form-control" type="number" step="0.01" value="0" placeholder="Taxa entrega"></div></div>
                <input type="hidden" name="subtotal" id="subtotalInput"><input type="hidden" name="total_amount" id="totalInput">
                <div class="mb-2"><textarea name="notes" class="form-control" placeholder="Observações do pedido"></textarea></div>
                <h4>Total: <span id="cartTotal">R$ 0,00</span></h4>
                <button class="btn btn-success w-100">Finalizar venda</button>
            </form>
        </div></div>
    </div>
</div>
