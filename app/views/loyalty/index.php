<div class="alert alert-secondary">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong>Status:</strong> <?= !empty($program['enabled']) ? 'Ativo' : 'Inativo' ?> ·
            <strong>Pontos por real:</strong> <?= number_format((float)($program['points_per_real'] ?? 0), 2, ',', '.') ?> ·
            <strong>Pedido mínimo:</strong> R$ <?= number_format((float)($program['min_order_value'] ?? 0), 2, ',', '.') ?> ·
            <strong>Saldo mínimo p/ resgate:</strong> <?= (int)($program['min_points_balance'] ?? 0) ?> pts
        </div>
        <a class="btn btn-sm btn-outline-primary" href="<?= base_url('/settings') ?>">Configurar fidelidade</a>
    </div>
</div>

<div class="card">
    <div class="card-header">Fidelidade</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Cliente</th><th>Saldo</th><th>Acumulados</th><th>Usados</th></tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Sem movimentações de fidelidade.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($r['name'] ?? '-')) ?></td>
                        <td><?= (int)($r['points_balance'] ?? 0) ?></td>
                        <td><?= (int)($r['points_earned'] ?? 0) ?></td>
                        <td><?= (int)($r['points_used'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
