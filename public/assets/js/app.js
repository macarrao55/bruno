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

  const addonsModal = document.getElementById('addonsModal');
  const addonsList = document.getElementById('addonsList');
  const addonsTotal = document.getElementById('addonsTotal');
  const addonsProductName = document.getElementById('addonsProductName');
  const addonsCloseBtn = document.getElementById('addonsCloseBtn');
  const addonsCancelBtn = document.getElementById('addonsCancelBtn');
  const addonsApplyBtn = document.getElementById('addonsApplyBtn');

  const format = (v) => `R$ ${Number(v).toFixed(2).replace('.', ',')}`;

  let addonsResolver = null;

  function closeAddonsModal(selected = null) {
    addonsModal?.classList.add('d-none');
    if (addonsResolver) {
      addonsResolver(selected || []);
      addonsResolver = null;
    }
  }

  function selectedAddonsFromModal(list) {
    if (!addonsList) return [];
    const checkedIds = [...addonsList.querySelectorAll('input[type="checkbox"]:checked')].map(i => Number(i.value));
    return list.filter(a => checkedIds.includes(Number(a.id))).map(a => ({ id: Number(a.id), name: a.name, price: Number(a.price) }));
  }

  function wireModalEvents(list) {
    const updateTotal = () => {
      const selected = selectedAddonsFromModal(list);
      const total = selected.reduce((s, a) => s + Number(a.price), 0);
      if (addonsTotal) addonsTotal.textContent = format(total);
    };

    addonsList?.querySelectorAll('input[type="checkbox"]').forEach(c => c.addEventListener('change', updateTotal));
    addonsCloseBtn && (addonsCloseBtn.onclick = () => closeAddonsModal([]));
    addonsCancelBtn && (addonsCancelBtn.onclick = () => closeAddonsModal([]));
    addonsApplyBtn && (addonsApplyBtn.onclick = () => closeAddonsModal(selectedAddonsFromModal(list)));

    addonsModal?.querySelector('.addons-backdrop')?.addEventListener('click', () => closeAddonsModal([]));
    updateTotal();
  }

  async function selectAddons(productId, productName) {
    const addonsUrl = document.getElementById('checkoutForm')?.action.replace('/checkout', '/addons') || 'addons';
    const res = await fetch(`${addonsUrl}?product_id=${productId}`);
    if (!res.ok) return [];
    const list = await res.json();
    if (!Array.isArray(list) || list.length === 0 || !addonsModal || !addonsList) return [];

    addonsProductName && (addonsProductName.textContent = `Produto: ${productName}`);
    addonsList.innerHTML = list.map(a => `
      <label class="addons-item">
        <input type="checkbox" value="${a.id}">
        <span>${a.name}</span>
        <strong>${format(a.price)}</strong>
      </label>
    `).join('');

    addonsModal.classList.remove('d-none');

    return new Promise(resolve => {
      addonsResolver = resolve;
      wireModalEvents(list);
    });
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
      const productName = btn.dataset.name;
      const addons = btn.dataset.allowsAddons === '1' ? await selectAddons(productId, productName) : [];
      const addonTotal = addons.reduce((s, a) => s + Number(a.price), 0);
      const unit = Number(btn.dataset.price) + addonTotal;
      cart.push({
        product_id: productId,
        product_name: productName,
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
