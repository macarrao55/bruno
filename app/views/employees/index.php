<div class="card">
    <div class="card-header">Funcionários</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Cargo</th>
                <th>Telefone</th>
                <th>Admissão</th>
                <th>Situação</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($employees)): ?>
                <tr><td colspan="5" class="text-muted text-center py-4">Nenhum funcionário cadastrado.</td></tr>
            <?php else: ?>
                <?php foreach ($employees as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($e['name'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($e['position'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($e['phone'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($e['admission_date'] ?? '-')) ?></td>
                        <td class="text-capitalize"><?= htmlspecialchars((string)($e['status'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
