<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
requireRole(['administrador', 'gerente', 'entregador']);

$entregadores = db()->query("SELECT id, nome FROM usuarios WHERE nivel = 'entregador' ORDER BY nome")->fetchAll();
$bairros = db()->query("SELECT DISTINCT c.bairro FROM entregas e LEFT JOIN clientes c ON c.id = e.cliente_id WHERE c.bairro IS NOT NULL AND c.bairro <> '' ORDER BY c.bairro")->fetchAll();
renderHeader('Mapa de Entregas - BoldriniSystem');
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<div class="row g-3 mb-3">
  <div class="col-md-2"><input type="date" id="fData" class="form-control" value="<?= date('Y-m-d') ?>"></div>
  <div class="col-md-2">
    <select id="fEntregador" class="form-select">
      <option value="">Entregador (todos)</option>
      <?php foreach ($entregadores as $e): ?><option value="<?= (int) $e['id'] ?>"><?= e($e['nome']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <select id="fStatus" class="form-select">
      <option value="">Status (todos)</option>
      <option value="preparando">Preparando</option>
      <option value="em rota">Em rota</option>
      <option value="entregue">Entregue</option>
      <option value="cancelado">Cancelado</option>
      <option value="atrasado">Atrasado</option>
    </select>
  </div>
  <div class="col-md-2">
    <select id="fBairro" class="form-select">
      <option value="">Bairro (todos)</option>
      <?php foreach ($bairros as $b): ?><option value="<?= e($b['bairro']) ?>"><?= e($b['bairro']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><button class="btn btn-primary w-100" id="btnAtualizar">Atualizar</button></div>
  <div class="col-md-2"><a class="btn btn-outline-success w-100" href="<?= BASE_URL ?>/pages/entregas.php">Nova entrega</a></div>
</div>

<div class="row g-3 mb-3" id="summaryCards">
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Total entregas</small><h4 id="sumTotal">0</h4></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Em aberto</small><h4 id="sumOpen">0</h4></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Entregues</small><h4 id="sumDone">0</h4></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Atrasados</small><h4 id="sumLate">0</h4></div></div></div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card"><div class="card-body" style="max-height:65vh; overflow:auto">
      <h6>Pedidos do dia</h6>
      <div id="listPedidos"></div>
    </div></div>
  </div>
  <div class="col-md-8">
    <div class="card mb-3"><div class="card-body p-0"><div id="map" style="height:50vh"></div></div></div>
    <div class="card"><div class="card-body" id="detailPedido"><em>Selecione um pedido para ver detalhes.</em></div></div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
const state = {items: [], markers: new Map(), selectedId: null};
const map = L.map('map').setView([-15.7801, -47.9292], 11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);

function statusColor(s){
  if(s==='preparando') return '#f6c23e';
  if(s==='em rota') return '#0d6efd';
  if(s==='entregue') return '#198754';
  if(s==='cancelado' || s==='atrasado') return '#dc3545';
  return '#6c757d';
}
function badge(s){ return `<span class="badge" style="background:${statusColor(s)}">${s}</span>`; }
function endereco(it){ return `${it.rua||''}, ${it.numero||''} - ${it.bairro||''}`.trim(); }
function mapsQuery(it){ return encodeURIComponent(`${it.rua||''} ${it.numero||''} ${it.bairro||''}`); }

function renderSummary(sum){
  document.getElementById('sumTotal').textContent = sum.total || 0;
  document.getElementById('sumOpen').textContent = sum.aberto || 0;
  document.getElementById('sumDone').textContent = sum.entregues || 0;
  document.getElementById('sumLate').textContent = sum.atrasados || 0;
}

function statusButtons(it){
  return ['em rota','entregue','cancelado'].map(s=>
    `<button class="btn btn-sm btn-outline-secondary me-1 mt-1" onclick="changeStatus(${it.id}, '${s}')">${s}</button>`
  ).join('');
}

function renderList(){
  const box = document.getElementById('listPedidos');
  if(!state.items.length){ box.innerHTML = '<div class="text-muted">Nenhum pedido para os filtros.</div>'; return; }
  box.innerHTML = state.items.map(it=>`
    <div class="card mb-2 pedido-card" data-id="${it.id}" style="border-left:5px solid ${statusColor(it.status)}; cursor:pointer" onclick="selectPedido(${it.id})">
      <div class="card-body p-2">
        <div class="d-flex justify-content-between"><strong>#${it.pedido}</strong>${badge(it.status)}</div>
        <div>${it.cliente||'Cliente não informado'}</div>
        <small>${it.telefone||'-'} · ${endereco(it)}</small><br>
        <small><strong>Bairro:</strong> ${it.bairro||'-'} | <strong>Entregador:</strong> ${it.entregador||'-'}</small><br>
        <small><strong>Horário:</strong> ${it.created_at||'-'}</small>
      </div>
    </div>
  `).join('');
}

function clearMarkers(){
  state.markers.forEach(m=>map.removeLayer(m));
  state.markers.clear();
}

function renderMap(){
  clearMarkers();
  const points = [];
  state.items.forEach(it=>{
    if(it.latitude && it.longitude){
      const lat = parseFloat(it.latitude), lng = parseFloat(it.longitude);
      const marker = L.circleMarker([lat,lng], {radius:8, color:statusColor(it.status), fillOpacity:0.9}).addTo(map);
      marker.bindPopup(`<strong>Pedido #${it.pedido}</strong><br>${it.cliente||''}<br>${it.telefone||''}<br>${endereco(it)}<br>Status: ${it.status}<br><a target="_blank" href="https://www.google.com/maps/search/?api=1&query=${mapsQuery(it)}">Abrir no Google Maps</a>`);
      state.markers.set(it.id, marker);
      points.push([lat,lng]);
    }
  });
  if(points.length){ map.fitBounds(points, {padding:[20,20]}); }
}

function renderDetail(it){
  const box = document.getElementById('detailPedido');
  box.innerHTML = `
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h6>Pedido #${it.pedido}</h6>
        <div><strong>Cliente:</strong> ${it.cliente||'-'} (${it.telefone||'-'})</div>
        <div><strong>Endereço:</strong> ${endereco(it)} ${it.referencia ? ' - '+it.referencia : ''}</div>
        <div><strong>Bairro:</strong> ${it.bairro||'-'} | <strong>Entregador:</strong> ${it.entregador||'-'}</div>
        <div><strong>Valor:</strong> R$ ${Number(it.valor_pedido||0).toFixed(2).replace('.',',')}</div>
        <div><strong>Status:</strong> ${badge(it.status)}</div>
      </div>
      <div>
        <a class="btn btn-sm btn-primary" target="_blank" href="https://www.google.com/maps/search/?api=1&query=${mapsQuery(it)}">Abrir rota no Google Maps</a>
      </div>
    </div>
    <div class="mt-2">${statusButtons(it)}</div>
  `;
}

function selectPedido(id){
  state.selectedId = id;
  const it = state.items.find(x=>Number(x.id)===Number(id));
  if(!it) return;
  renderDetail(it);
  document.querySelectorAll('.pedido-card').forEach(c=>c.classList.remove('border-dark'));
  const el = document.querySelector(`.pedido-card[data-id="${id}"]`);
  if(el) el.classList.add('border-dark');

  const marker = state.markers.get(id);
  if(marker){ marker.openPopup(); map.panTo(marker.getLatLng()); }
}

async function loadData(){
  const qs = new URLSearchParams({
    data: document.getElementById('fData').value,
    entregador_id: document.getElementById('fEntregador').value,
    status: document.getElementById('fStatus').value,
    bairro: document.getElementById('fBairro').value,
  });
  const r = await fetch(`<?= BASE_URL ?>/actions/mapa_entregas_data.php?${qs.toString()}`);
  const data = await r.json();
  state.items = data.items || [];
  renderSummary(data.summary || {});
  renderList();
  renderMap();
  document.getElementById('detailPedido').innerHTML = '<em>Selecione um pedido para ver detalhes.</em>';
}

async function changeStatus(id, status){
  const body = new URLSearchParams({id:String(id), status});
  const r = await fetch('<?= BASE_URL ?>/actions/update_entrega_status.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body});
  const data = await r.json();
  if(!data.ok){ alert(data.message || 'Erro ao atualizar status'); return; }
  await loadData();
  selectPedido(id);
}

window.changeStatus = changeStatus;
window.selectPedido = selectPedido;

document.getElementById('btnAtualizar').addEventListener('click', loadData);
['fData','fEntregador','fStatus','fBairro'].forEach(id=>document.getElementById(id).addEventListener('change', loadData));
loadData();
</script>
<?php renderFooter(); ?>
