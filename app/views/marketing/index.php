<div class="card">
    <div class="card-header">Campanhas de Marketing</div>
    <div class="card-body">
        <p class="mb-0">Segmentação por bairro, aniversário, inatividade, frequência e gasto.</p>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Nome</th><th>Tipo</th><th>Status</th><th>Início</th><th>Fim</th></tr>
            </thead>
            <tbody>
            <?php if (empty($campaigns)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma campanha cadastrada.</td></tr>
            <?php else: ?>
                <?php foreach ($campaigns as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($c['name'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($c['campaign_type'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($c['status'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($c['start_date'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($c['end_date'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
