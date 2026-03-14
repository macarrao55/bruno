<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$hasBloqueado = tableHasColumn('clientes', 'bloqueado');
$clientesSql = $hasBloqueado
    ? 'SELECT id,nome FROM clientes WHERE bloqueado = 0 ORDER BY nome'
    : 'SELECT id,nome FROM clientes ORDER BY nome';
$clientes = db()->query($clientesSql)->fetchAll();
$produtos = db()->query('SELECT * FROM produtos ORDER BY nome')->fetchAll();
renderHeader('PDV Moderno');
?>
<div class="card mb-3"><div class="card-body bg-light">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Cadastro rápido de cliente (sem sair do PDV)</h6>
    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#quickClient">+ Cliente rápido</button>
  </div>
  <div class="collapse" id="quickClient">
    <form method="post" action="<?= BASE_URL ?>/actions/save_cliente.php" class="row g-2">
      <input type="hidden" name="redirect_to" value="pages/pdv.php">
      <div class="col-md-3"><input name="nome" class="form-control" placeholder="Nome" required></div>
      <div class="col-md-2"><input name="telefone" class="form-control" placeholder="Telefone"></div>
      <div class="col-md-2"><input name="cep" class="form-control" placeholder="CEP"></div>
      <div class="col-md-2"><input name="rua" class="form-control" placeholder="Rua"></div>
      <div class="col-md-1"><input name="numero" class="form-control" placeholder="Nº"></div>
      <div class="col-md-2"><input name="bairro" class="form-control" placeholder="Bairro"></div>
      <div class="col-md-2"><input name="referencia" class="form-control" placeholder="Referência"></div>
      <div class="col-md-2"><input name="cpf" class="form-control" placeholder="CPF"></div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Salvar</button></div>
    </form>
  </div>
</div></div>

<div class="row">
  <div class="col-md-8">
    <form method="post" action="<?= BASE_URL ?>/actions/finalizar_venda.php" id="pdvForm">
      <div class="card mb-3"><div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-md-4"><label>Cliente</label><select name="cliente_id" class="form-select"><option value="">Não cadastrado</option><?php foreach($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-3"><label>Pagamento</label><select name="forma_pagamento" class="form-select"><option>Dinheiro</option><option>Pix</option><option>Cartão</option><option>Crediário</option><option>Cheque</option></select></div>
          <div class="col-md-3"><label>Recebimento</label><select name="recebimento_status" class="form-select"><option value="recebido">Já recebeu</option><option value="na_entrega">Vai receber na entrega</option></select></div>
          <div class="col-md-2"><label>Valor pago</label><input type="number" step="0.01" name="valor_pago" id="valorPago" class="form-control" value="0"></div>
          <div class="col-md-2"><label>Troco</label><input readonly id="troco" class="form-control" value="0,00"></div>
        </div>
      </div></div>

      <div class="card"><div class="card-body">
        <h6>Buscar produto por código de barras, código interno ou nome</h6>
        <input type="text" id="buscaProduto" class="form-control mb-2" placeholder="Digite para filtrar produtos...">
        <div class="table-responsive" style="max-height:300px;overflow:auto">
          <table class="table table-sm" id="tabelaProdutos"><thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th></th></tr></thead><tbody>
          <?php foreach($produtos as $p): ?>
            <tr data-text="<?= e(strtolower($p['nome'].' '.$p['codigo_barras'].' '.$p['codigo_interno'].' '.$p['categoria'])) ?>">
              <td><?= e($p['nome']) ?></td><td><?= e($p['categoria']) ?></td><td><?= money((float)$p['preco_venda']) ?></td>
              <td><button type="button" class="btn btn-sm btn-outline-primary" onclick='addItem(<?= json_encode($p) ?>)'>Adicionar</button></td>
            </tr>
          <?php endforeach; ?>
          </tbody></table>
        </div>
      </div></div>
  </div>
  <div class="col-md-4">
    <div class="card sticky-top" style="top:12px"><div class="card-body">
      <h5>Carrinho</h5>
      <div id="cartList" class="small"></div>
      <input type="hidden" name="itens_json" id="itensJson">
      <div class="mt-3"><label>Desconto total</label><input type="number" step="0.01" name="desconto_total" id="descontoTotal" class="form-control" value="0"></div>
      <h4 class="mt-3">Total: <span id="totalVenda">R$ 0,00</span></h4>
      <button class="btn btn-success w-100 mt-2">Finalizar venda</button>
      <a class="btn btn-outline-danger w-100 mt-2" href="<?= BASE_URL ?>/pages/pdv.php">Cancelar venda</a>
      <a class="btn btn-outline-secondary w-100 mt-2" href="<?= BASE_URL ?>/pages/vendas.php">Ver/reimprimir vendas</a>
    </div></div>
    </form>
  </div>
</div>
<script>
let cart = [];
const money = v => `R$ ${Number(v).toFixed(2).replace('.',',')}`;
function isGalao(c){ return String(c || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'') === 'galao'; }
function addItem(p){
  // Regra solicitada: não acumular produtos iguais; cada clique vira uma linha separada no carrinho.
  cart.push({id:p.id,nome:p.nome,categoria:p.categoria,preco:Number(p.preco_venda),custo:Number(p.custo),quantidade:1,desconto:0,validade_galao:''});
  renderCart();
}
function renderCart(){
  const list = document.getElementById('cartList');
  list.innerHTML = cart.map((i,idx)=>{
    const validadeField = isGalao(i.categoria)
      ? `<br>Validade galão <input type='date' value='${i.validade_galao || ''}' onchange='upd(${idx},"v",this.value)' required>`
      : '';
    return `<div class='border rounded p-2 mb-2'>${i.nome} <span class='text-muted'>(${i.categoria || 'Geral'})</span><br>Qtd <input type='number' min='1' value='${i.quantidade}' onchange='upd(${idx},"q",this.value)'> Preço <input type='number' step='0.01' value='${i.preco}' onchange='upd(${idx},"p",this.value)'> Desc <input type='number' step='0.01' value='${i.desconto}' onchange='upd(${idx},"d",this.value)'>${validadeField}</div>`;
  }).join('');
  const subtotal = cart.reduce((s,i)=>s + (i.preco*i.quantidade)-Number(i.desconto||0),0);
  const total = subtotal - Number(document.getElementById('descontoTotal').value || 0);
  document.getElementById('totalVenda').innerText = money(total);
  document.getElementById('itensJson').value = JSON.stringify(cart);
  const pago = Number(document.getElementById('valorPago').value || 0);
  document.getElementById('troco').value = (pago-total).toFixed(2).replace('.',',');
}
function upd(idx,t,v){
  if(t==='q')cart[idx].quantidade=Number(v);
  if(t==='p')cart[idx].preco=Number(v);
  if(t==='d')cart[idx].desconto=Number(v);
  if(t==='v')cart[idx].validade_galao=v;
  renderCart();
}

document.getElementById('descontoTotal').addEventListener('input', renderCart);
document.getElementById('valorPago').addEventListener('input', renderCart);
document.getElementById('buscaProduto').addEventListener('input', function(){
  const q = this.value.toLowerCase();
  document.querySelectorAll('#tabelaProdutos tbody tr').forEach(tr=>{tr.style.display = tr.dataset.text.includes(q)?'':'none';});
});

document.getElementById('pdvForm').addEventListener('submit', function(e){
  const faltando = cart.find(i => isGalao(i.categoria) && !i.validade_galao);
  if (faltando) {
    e.preventDefault();
    alert('Informe a validade do galão para todos os itens da categoria Galão.');
  }
});
</script>
<?php renderFooter(); ?>
