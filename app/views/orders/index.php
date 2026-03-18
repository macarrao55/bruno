<div class="card">
    <div class="card-header">Pedidos e análise de tempo</div>
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
            <?php foreach (($orders ?? []) as $o): ?>
                <tr>
                    <td><?= (int)$o['id'] ?></td>
                    <td><?= htmlspecialchars((string)($o['customer_name'] ?? 'Consumidor')) ?></td>
                    <td><?= htmlspecialchars((string)$o['order_type']) ?></td>
                    <td><?= htmlspecialchars((string)$o['payment_method']) ?></td>
                    <td><?= htmlspecialchars((string)$o['status']) ?></td>
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
                                <?php foreach (['novo','em preparo','pronto','entregue','cancelado'] as $status): ?>
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
            </tbody>
        </table>
    </div>
</div>
