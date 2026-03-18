<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Novo insumo</div>
            <div class="card-body">
                <form method="post" action="<?= base_url('/stock/store') ?>" class="row g-2">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label">Nome</label>
                        <input name="name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unidade</label>
                        <input name="unit" class="form-control" placeholder="kg, un, L" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estoque atual</label>
                        <input name="current_stock" type="number" min="0" step="0.001" class="form-control" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estoque mínimo</label>
                        <input name="min_stock" type="number" min="0" step="0.001" class="form-control" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Custo médio (R$)</label>
                        <input name="average_cost" type="number" min="0" step="0.01" class="form-control" value="0">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100">Adicionar insumo</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Configurar ficha técnica (baixa automática)</div>
            <div class="card-body">
                <form method="post" action="<?= base_url('/stock/recipes/store') ?>" class="row g-2">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label">Produto</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach (($products ?? []) as $product): ?>
                                <option value="<?= (int)$product['id'] ?>"><?= htmlspecialchars((string)$product['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Insumo</label>
                        <select name="stock_item_id" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach (($items ?? []) as $item): ?>
                                <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars((string)$item['name']) ?> (<?= htmlspecialchars((string)$item['unit']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Qtd usada por unidade vendida</label>
                        <input name="quantity_used" type="number" min="0.001" step="0.001" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-primary w-100">Salvar ficha técnica</button>
                    </div>
                </form>
                <small class="text-muted d-block mt-2">
                    Ao salvar, o produto passa a controlar estoque e cada venda no PDV dá baixa automática nos insumos vinculados.
                </small>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header">Insumos cadastrados</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Insumo</th>
                        <th>Unid.</th>
                        <th>Atual</th>
                        <th>Mín.</th>
                        <th>Custo</th>
                        <th>Status</th>
                        <th style="min-width: 290px;">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Sem itens de estoque cadastrados.</td></tr>
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
                                <td>R$ <?= number_format((float)($i['average_cost'] ?? 0), 2, ',', '.') ?></td>
                                <td><span class="badge <?= $current <= $minimum ? 'text-bg-danger' : 'text-bg-success' ?>"><?= $current <= $minimum ? 'Baixo' : 'OK' ?></span></td>
                                <td>
                                    <details>
                                        <summary class="btn btn-sm btn-outline-secondary d-inline-block">Editar</summary>
                                        <form method="post" action="<?= base_url('/stock/update') ?>" class="mt-2 row g-2">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                            <div class="col-md-6"><input name="name" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$i['name']) ?>" required></div>
                                            <div class="col-md-2"><input name="unit" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$i['unit']) ?>" required></div>
                                            <div class="col-md-2"><input name="current_stock" type="number" step="0.001" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$i['current_stock']) ?>"></div>
                                            <div class="col-md-2"><input name="min_stock" type="number" step="0.001" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$i['min_stock']) ?>"></div>
                                            <div class="col-md-6"><input name="average_cost" type="number" step="0.01" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($i['average_cost'] ?? '0')) ?>"></div>
                                            <div class="col-md-6"><button class="btn btn-sm btn-primary w-100">Salvar</button></div>
                                        </form>
                                    </details>
                                    <form method="post" action="<?= base_url('/stock/delete') ?>" class="mt-2" onsubmit="return confirm('Excluir este insumo?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Ficha técnica dos produtos</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Insumo</th>
                        <th>Consumo por venda</th>
                        <th>Ação</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recipes)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Nenhuma ficha técnica configurada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recipes as $recipe): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$recipe['product_name']) ?></td>
                                <td><?= htmlspecialchars((string)$recipe['stock_item_name']) ?> (<?= htmlspecialchars((string)$recipe['stock_item_unit']) ?>)</td>
                                <td><?= number_format((float)$recipe['quantity_used'], 3, ',', '.') ?></td>
                                <td>
                                    <form method="post" action="<?= base_url('/stock/recipes/delete') ?>" onsubmit="return confirm('Remover insumo da ficha técnica?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$recipe['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
