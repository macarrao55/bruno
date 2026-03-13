<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$hasBloqueado = tableHasColumn('clientes', 'bloqueado');
$clientes = db()->query('SELECT * FROM clientes ORDER BY id DESC')->fetchAll();
renderHeader('Clientes');
?>
<div class="card mb-3"><div class="card-body">
  <h6>Novo cliente</h6>
  <form class="row g-2 mb-2" method="post" action="<?= BASE_URL ?>/actions/save_cliente.php">
    <div class="col-md-3"><input name="nome" class="form-control" placeholder="Nome" required></div>
    <div class="col-md-2"><input name="telefone" class="form-control" placeholder="Telefone"></div>
    <div class="col-md-2"><input name="rua" class="form-control" placeholder="Rua"></div>
    <div class="col-md-1"><input name="numero" class="form-control" placeholder="Nº"></div>
    <div class="col-md-2"><input name="bairro" class="form-control" placeholder="Bairro"></div>
    <div class="col-md-2"><input name="referencia" class="form-control" placeholder="Referência"></div>
    <div class="col-md-2"><input name="cpf" class="form-control" placeholder="CPF"></div>
    <div class="col-md-2">
      <select name="tipo_cliente" class="form-select"><option value="comum">Comum</option><option value="revendedor">Revendedor</option></select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary">Salvar cliente</button></div>
  </form>
</div></div>

<div class="card"><div class="card-body">
  <h6>Gerenciar clientes (editar, bloquear/desbloquear, excluir)</h6>
  <?php if (!$hasBloqueado): ?>
    <div class="alert alert-warning py-2">
      Seu banco está sem a coluna <code>clientes.bloqueado</code>. O botão de bloqueio fica desabilitado até executar:
      <code>ALTER TABLE clientes ADD COLUMN bloqueado TINYINT(1) DEFAULT 0;</code>
    </div>
  <?php endif; ?>
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Nome</th><th>Telefone</th><th>Rua</th><th>Nº</th><th>Bairro</th><th>Referência</th><th>CPF</th><th>Tipo</th><th>Status</th><th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($clientes as $c): $bloqueado = (int)($c['bloqueado'] ?? 0); ?>
        <tr class="<?= $bloqueado === 1 ? 'table-warning' : '' ?>">
          <form method="post" action="<?= BASE_URL ?>/actions/update_cliente.php">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <td><input name="nome" class="form-control form-control-sm" value="<?= e($c['nome']) ?>" required></td>
            <td><input name="telefone" class="form-control form-control-sm" value="<?= e($c['telefone']) ?>"></td>
            <td><input name="rua" class="form-control form-control-sm" value="<?= e($c['rua']) ?>"></td>
            <td><input name="numero" class="form-control form-control-sm" value="<?= e($c['numero']) ?>"></td>
            <td><input name="bairro" class="form-control form-control-sm" value="<?= e($c['bairro']) ?>"></td>
            <td><input name="referencia" class="form-control form-control-sm" value="<?= e($c['referencia']) ?>"></td>
            <td><input name="cpf" class="form-control form-control-sm" value="<?= e($c['cpf']) ?>"></td>
            <td>
              <select name="tipo_cliente" class="form-select form-select-sm">
                <option value="comum" <?= $c['tipo_cliente'] === 'comum' ? 'selected' : '' ?>>Comum</option>
                <option value="revendedor" <?= $c['tipo_cliente'] === 'revendedor' ? 'selected' : '' ?>>Revendedor</option>
              </select>
            </td>
            <td><?= $bloqueado === 1 ? '<span class="badge bg-warning text-dark">Bloqueado</span>' : '<span class="badge bg-success">Ativo</span>' ?></td>
            <td class="d-flex gap-1">
              <button class="btn btn-sm btn-primary" type="submit">Editar</button>
          </form>
              <form method="post" action="<?= BASE_URL ?>/actions/toggle_block_cliente.php" onsubmit="return confirm('Alterar status de bloqueio deste cliente?');">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn btn-sm btn-outline-warning" type="submit" <?= !$hasBloqueado ? 'disabled title="Atualize o banco para habilitar"' : '' ?>><?= $bloqueado === 1 ? 'Desbloquear' : 'Bloquear' ?></button>
              </form>
              <form method="post" action="<?= BASE_URL ?>/actions/delete_cliente.php" onsubmit="return confirm('Excluir cliente? Essa ação não pode ser desfeita.');">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
              </form>
            </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div></div>
<?php renderFooter(); ?>
