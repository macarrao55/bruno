let cart=[];
function renderCart(){
  const ul=document.getElementById('cartItems');ul.innerHTML='';
  let total=0;
  cart.forEach((it,idx)=>{total+=it.price*it.qty;const li=document.createElement('li');li.textContent=`${it.qty}x ${it.name} - R$ ${(it.price*it.qty).toFixed(2)}`;li.onclick=()=>{if(confirm('Remover item?')){cart.splice(idx,1);renderCart();}};ul.appendChild(li);});
  document.getElementById('total').textContent=`R$ ${total.toFixed(2)}`;
}
function addToCart(id,name,price){
  const i=cart.find(x=>x.product_id===id);if(i){i.qty++;}else{cart.push({product_id:id,name,price,qty:1,notes:''});}
  renderCart();
}
async function submitOrder(){
  const payload={items:cart,payment_method:pm('payment_method'),client_id:pm('client_id')||null,discount_value:pm('discount_value')||0,discount_type:pm('discount_type'),service_fee:pm('service_fee')||0,origin:'balcao',order_type:'retirada'};
  const r=await fetch('/orders/create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  const j=await r.json();
  if(j.ok){alert('Pedido #'+j.order_id+' criado');cart=[];renderCart();}
  else{alert(j.error||'Falha ao salvar pedido');}
}
function pm(id){return document.getElementById(id).value}
document.addEventListener('keydown',(e)=>{
  if(e.key==='F2'){e.preventDefault();document.getElementById('productSearch').focus();}
  if(e.key==='F3'){e.preventDefault();document.getElementById('cartCard').scrollIntoView();}
  if(e.key==='F4'){e.preventDefault();submitOrder();}
  if(e.key==='Escape'){cart.pop();renderCart();}
  if(e.ctrlKey && e.key.toLowerCase()==='s'){e.preventDefault();submitOrder();}
});
document.getElementById('productSearch')?.addEventListener('input',(e)=>{const q=e.target.value.toLowerCase();document.querySelectorAll('.product-btn').forEach(b=>b.style.display=b.textContent.toLowerCase().includes(q)?'block':'none')});
