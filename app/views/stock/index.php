<div class="card">
    <div class="card-header">Estoque e Ficha Técnica</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Insumo</th><th>Unidade</th><th>Atual</th><th>Mínimo</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Sem itens de estoque cadastrados.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $i): ?>
                    <?php
                    $current = (float)($i['current_stock'] ?? 0);
                    $minimum = (float)($i['min_stock'] ?? 0);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($i['name'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($i['unit'] ?? '-')) ?></td>
                        <td><?= number_format($current, 3, ',', '.') ?></td>
                        <td><?= number_format($minimum, 3, ',', '.') ?></td>
                        <td><span class="badge <?= $current <= $minimum ? 'text-bg-danger' : 'text-bg-success' ?>"><?= $current <= $minimum ? 'Baixo' : 'OK' ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
