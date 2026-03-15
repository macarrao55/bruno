(() => {
  const products = document.querySelectorAll('.pdv-product');
  if (!products.length) return;

  const cart = [];
  const cartRoot = document.getElementById('cartItems');
  const itemsJson = document.getElementById('itemsJson');
  const subtotalInput = document.getElementById('subtotalInput');
  const totalInput = document.getElementById('totalInput');
  const totalEl = document.getElementById('cartTotal');
  const discountEl = document.getElementById('discount');
  const deliveryEl = document.getElementById('deliveryFee');
  const amountReceived = document.getElementById('amountReceived');
  const changeAmount = document.getElementById('changeAmount');
  const changeText = document.getElementById('changeText');
  const paymentMethod = document.getElementById('paymentMethod');

  const format = (v) => `R$ ${Number(v).toFixed(2).replace('.', ',')}`;

  async function selectAddons(productId) {
    const addonsUrl = document.getElementById('checkoutForm')?.action.replace('/checkout', '/addons') || 'addons';
    const res = await fetch(`${addonsUrl}?product_id=${productId}`);
    if (!res.ok) return [];
    const list = await res.json();
    if (!Array.isArray(list) || list.length === 0) return [];
    const names = list.map(a => `${a.id}:${a.name}:${a.price}`).join('\n');
    const raw = prompt(`Adicionais (id:nome:preço)\n${names}\nDigite IDs separados por vírgula ou deixe vazio:`);
    if (!raw) return [];
    const ids = raw.split(',').map(s => Number(s.trim())).filter(Boolean);
    return list.filter(a => ids.includes(Number(a.id))).map(a => ({ id: Number(a.id), name: a.name, price: Number(a.price) }));
  }

  function recalc() {
    let subtotal = 0;
    cart.forEach(i => subtotal += i.total_price);
    const discount = Number(discountEl?.value || 0);
    const delivery = Number(deliveryEl?.value || 0);
    const total = Math.max(subtotal - discount + delivery, 0);
    const received = Number(amountReceived?.value || 0);
    const change = paymentMethod?.value === 'dinheiro' ? Math.max(received - total, 0) : 0;

    if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
    if (totalInput) totalInput.value = total.toFixed(2);
    if (changeAmount) changeAmount.value = change.toFixed(2);
    if (totalEl) totalEl.textContent = format(total);
    if (changeText) changeText.textContent = format(change);
    if (itemsJson) itemsJson.value = JSON.stringify(cart);

    cartRoot.innerHTML = cart.map((item, idx) => `<div class="border-bottom py-1">
      <div class="d-flex justify-content-between"><strong>${item.quantity}x ${item.product_name}</strong><button type="button" class="btn btn-sm btn-link text-danger" data-rm="${idx}">x</button></div>
      <small>${item.addons.map(a => '+' + a.name).join(', ') || ''}</small><br>
      <span>${format(item.total_price)}</span>
    </div>`).join('');

    cartRoot.querySelectorAll('[data-rm]').forEach(b => b.onclick = () => { cart.splice(Number(b.dataset.rm), 1); recalc(); });
  }

  products.forEach(btn => {
    btn.addEventListener('click', async () => {
      const productId = Number(btn.dataset.id);
      const addons = btn.dataset.allowsAddons === '1' ? await selectAddons(productId) : [];
      const addonTotal = addons.reduce((s, a) => s + Number(a.price), 0);
      const unit = Number(btn.dataset.price) + addonTotal;
      cart.push({
        product_id: productId,
        product_name: btn.dataset.name,
        quantity: 1,
        unit_price: Number(btn.dataset.price),
        unit_cost: Number(btn.dataset.cost),
        total_price: unit,
        controls_stock: Number(btn.dataset.controlsStock),
        addons,
        notes: ''
      });
      recalc();
    });
  });

  [discountEl, deliveryEl, amountReceived, paymentMethod].forEach(i => i && i.addEventListener('input', recalc));

  document.getElementById('productSearch')?.addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('.pdv-item').forEach(it => it.style.display = it.dataset.name.includes(q) ? '' : 'none');
  });
  document.getElementById('categoryFilter')?.addEventListener('change', (e) => {
    const cat = e.target.value;
    document.querySelectorAll('.pdv-item').forEach(it => it.style.display = !cat || it.dataset.category === cat ? '' : 'none');
  });

  const form = document.getElementById('checkoutForm');
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (cart.length === 0) return alert('Carrinho vazio');
    const fd = new FormData(form);
    const res = await fetch(form.action, { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.ok) return alert(data.message || 'Erro');
    alert('Pedido #' + data.order_id + ' finalizado');
    window.location.href = window.location.pathname.replace('/pdv','/orders');
  });
})();
