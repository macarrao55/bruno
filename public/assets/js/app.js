(() => {
  const html = document.documentElement;
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-bs-theme', next);
      localStorage.setItem('theme', next);
    });
    const saved = localStorage.getItem('theme');
    if (saved) html.setAttribute('data-bs-theme', saved);
  }

  if (window.dashboardSales) {
    const ctx = document.getElementById('salesChart');
    if (ctx) {
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: window.dashboardSales.map(i => i.day),
          datasets: [{ label: 'Vendas', data: window.dashboardSales.map(i => Number(i.total)), borderColor: '#0d6efd' }]
        }
      });
    }
  }

  const products = document.querySelectorAll('.pdv-product');
  const cartItems = [];
  const cartRoot = document.getElementById('cartItems');
  const totalEl = document.getElementById('cartTotal');
  const itemsJson = document.getElementById('itemsJson');
  const subtotalInput = document.getElementById('subtotalInput');
  const totalInput = document.getElementById('totalInput');
  const discountInput = document.getElementById('discount');
  const deliveryFeeInput = document.getElementById('deliveryFee');

  function renderCart() {
    if (!cartRoot) return;
    let subtotal = 0;
    cartRoot.innerHTML = cartItems.map((item, idx) => {
      subtotal += item.total_price;
      return `<div class="cart-line d-flex justify-content-between"><div>${item.quantity}x ${item.product_name}</div><div>R$ ${item.total_price.toFixed(2)} <button type="button" data-rm="${idx}" class="btn btn-sm btn-link text-danger">x</button></div></div>`;
    }).join('');

    const discount = Number(discountInput?.value || 0);
    const delivery = Number(deliveryFeeInput?.value || 0);
    const total = Math.max(subtotal - discount + delivery, 0);

    if (totalEl) totalEl.textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
    if (itemsJson) itemsJson.value = JSON.stringify(cartItems);
    if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
    if (totalInput) totalInput.value = total.toFixed(2);

    cartRoot.querySelectorAll('[data-rm]').forEach(btn => btn.addEventListener('click', () => {
      cartItems.splice(Number(btn.getAttribute('data-rm')), 1);
      renderCart();
    }));
  }

  products.forEach(button => {
    button.addEventListener('click', () => {
      const id = Number(button.dataset.id);
      const name = button.dataset.name;
      const price = Number(button.dataset.price);
      const existing = cartItems.find(i => i.product_id === id);
      if (existing) {
        existing.quantity += 1;
        existing.total_price = existing.quantity * existing.unit_price;
      } else {
        cartItems.push({ product_id: id, product_name: name, quantity: 1, unit_price: price, total_price: price, notes: '' });
      }
      renderCart();
    });
  });

  [discountInput, deliveryFeeInput].forEach(el => el && el.addEventListener('input', renderCart));

  const search = document.getElementById('productSearch');
  const filter = document.getElementById('categoryFilter');
  const items = document.querySelectorAll('.pdv-item');

  function applyFilter() {
    const q = (search?.value || '').toLowerCase();
    const cat = filter?.value || '';
    items.forEach(item => {
      const okName = item.dataset.name.includes(q);
      const okCat = !cat || item.dataset.category === cat;
      item.style.display = okName && okCat ? '' : 'none';
    });
  }
  search?.addEventListener('input', applyFilter);
  filter?.addEventListener('change', applyFilter);

  const checkoutForm = document.getElementById('checkoutForm');
  checkoutForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (cartItems.length === 0) return alert('Carrinho vazio');

    const fd = new FormData(checkoutForm);
    const res = await fetch(checkoutForm.action, { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.ok) return alert(data.message || 'Erro ao finalizar');
    alert(`Pedido #${data.order_id} finalizado!`);
    window.location.href = `${window.location.origin}${window.location.pathname.replace('/pdv', '/orders')}`;
  });
})();
