<?php
$filters = $filters ?? [];
$statuses = ['novo','em preparo','pronto','entregue','cancelado'];
$types = ['balcao','delivery','retirada'];
$payments = ['dinheiro','pix','cartao','crediario'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Pedidos e análise de tempo</h5>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm" id="openOrderFilterBtn">Filtros de pedidos</button>
        <a href="<?= base_url('/orders') ?>" class="btn btn-outline-secondary btn-sm">Limpar filtros</a>
    </div>
</div>

<div id="ordersFilterModal" class="addons-modal d-none" role="dialog" aria-modal="true" aria-labelledby="ordersFilterTitle">
    <div class="addons-backdrop"></div>
    <div class="addons-panel card shadow-lg">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong id="ordersFilterTitle">Como deseja filtrar os pedidos?</strong>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="ordersFilterCloseBtn">Fechar</button>
        </div>
        <div class="card-body">
            <form method="get" action="<?= base_url('/orders') ?>" id="ordersFilterForm" class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= $status ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <input name="customer" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filters['customer'] ?? '')) ?>" placeholder="Nome do cliente">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo</label>
                    <select name="order_type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= $type ?>" <?= ($filters['order_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pagamento</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($payments as $payment): ?>
                            <option value="<?= $payment ?>" <?= ($filters['payment_method'] ?? '') === $payment ? 'selected' : '' ?>><?= $payment ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Data exata</label>
                    <input name="date" type="date" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filters['date'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data de</label>
                    <input name="date_from" type="date" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filters['date_from'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data até</label>
                    <input name="date_to" type="date" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filters['date_to'] ?? '')) ?>">
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" id="ordersFilterCancelBtn">Cancelar</button>
            <button type="submit" class="btn btn-primary" form="ordersFilterForm">Aplicar filtros</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Pagamento</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Venda</th>
                    <th>Até preparo</th>
                    <th>Até entrega</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Nenhum pedido encontrado para os filtros informados.</td></tr>
            <?php else: ?>
                <?php foreach (($orders ?? []) as $o): ?>
                    <?php
                    $rowClass = '';
                    if (($o['status'] ?? '') === 'entregue') {
                        $rowClass = 'table-success';
                    } elseif (($o['status'] ?? '') === 'pronto') {
                        $rowClass = 'table-warning';
                    } elseif (($o['status'] ?? '') === 'cancelado') {
                        $rowClass = 'table-danger';
                    } elseif (($o['status'] ?? '') === 'em preparo') {
                        $rowClass = 'table-preparo';
                    }
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td><?= (int)$o['id'] ?></td>
                        <td><?= htmlspecialchars((string)($o['customer_name'] ?? 'Consumidor')) ?></td>
                        <td><?= htmlspecialchars((string)$o['order_type']) ?></td>
                        <td><?= htmlspecialchars((string)$o['payment_method']) ?></td>
                        <td><strong><?= htmlspecialchars((string)$o['status']) ?></strong></td>
                        <td>R$ <?= number_format((float)$o['total_amount'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string)($o['created_at'] ?? '-')) ?></td>
                        <td>
                            <?php if (($o['minutes_to_prep'] ?? null) !== null): ?>
                                <?= (int)$o['minutes_to_prep'] ?> min
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($o['minutes_to_delivery'] ?? null) !== null): ?>
                                <?= (int)$o['minutes_to_delivery'] ?> min
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="<?= base_url('/orders/status') ?>" class="d-flex gap-1 flex-wrap">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= $status ?>" <?= ($o['status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary btn-sm">Atualizar</button>
                                <a class="btn btn-outline-dark btn-sm" target="_blank" href="<?= base_url('/orders/print/client?id=' . $o['id']) ?>">Cliente</a>
                                <a class="btn btn-outline-secondary btn-sm" target="_blank" href="<?= base_url('/orders/print/kitchen?id=' . $o['id']) ?>">Cozinha</a>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(() => {
  const modal = document.getElementById('ordersFilterModal');
  if (!modal) return;

  const openBtn = document.getElementById('openOrderFilterBtn');
  const closeBtn = document.getElementById('ordersFilterCloseBtn');
  const cancelBtn = document.getElementById('ordersFilterCancelBtn');
  const backdrop = modal.querySelector('.addons-backdrop');

  const open = () => modal.classList.remove('d-none');
  const close = () => modal.classList.add('d-none');

  openBtn?.addEventListener('click', open);
  closeBtn?.addEventListener('click', close);
  cancelBtn?.addEventListener('click', close);
  backdrop?.addEventListener('click', close);
})();
</script>
