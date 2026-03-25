<div class="card">
    <div class="card-header">Auditoria e Logs</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Entidade</th>
                    <th>IP</th>
                    <th>Data/Hora</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" class="text-muted text-center py-4">Nenhum log de auditoria encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td>#<?= htmlspecialchars((string)($l['user_id'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($l['action'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($l['entity'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($l['ip'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($l['created_at'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
