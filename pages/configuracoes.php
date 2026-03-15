<?php
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
requireRole(['administrador', 'gerente']);

$defaults = [
    'app_nome' => APP_NAME,
    'print_empresa_nome' => PRINT_EMPRESA_NOME,
    'print_empresa_telefone' => PRINT_EMPRESA_TELEFONE,
    'print_empresa_instagram' => PRINT_EMPRESA_INSTAGRAM,
    'print_crediario_segunda_via' => PRINT_CREDIARIO_SEGUNDA_VIA ? '1' : '0',
    'print_rodape_texto' => PRINT_RODAPE_TEXTO,
    'crediario_dias_vencimento' => '30',
    'formas_pagamento' => 'Dinheiro, Pix, Cartão, Crediário, Cheque',
];

$config = [];
foreach ($defaults as $key => $default) {
    $config[$key] = getSetting($key, $default);
}

renderHeader('Configurações do Sistema');
?>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success"><?= e($_GET['ok']) ?></div>
<?php endif; ?>
<?php if (!empty($_GET['erro'])): ?>
  <div class="alert alert-danger"><?= e($_GET['erro']) ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-body">
    <h5 class="mb-3">Configurações Gerais</h5>
    <form method="post" action="<?= BASE_URL ?>/actions/save_configuracoes.php">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nome do Sistema</label>
          <input type="text" class="form-control" name="app_nome" value="<?= e($config['app_nome']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Dias para vencimento no crediário</label>
          <input type="number" min="1" max="365" class="form-control" name="crediario_dias_vencimento" value="<?= e($config['crediario_dias_vencimento']) ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label">Formas de pagamento (separadas por vírgula)</label>
          <input type="text" class="form-control" name="formas_pagamento" value="<?= e($config['formas_pagamento']) ?>" required>
          <small class="text-muted">Exemplo: Dinheiro, Pix, Cartão, Crediário, Cheque</small>
        </div>
      </div>

      <hr>
      <h6 class="mb-3">Impressão térmica</h6>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Nome da empresa</label>
          <input type="text" class="form-control" name="print_empresa_nome" value="<?= e($config['print_empresa_nome']) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Telefone da empresa</label>
          <input type="text" class="form-control" name="print_empresa_telefone" value="<?= e($config['print_empresa_telefone']) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Instagram da empresa</label>
          <input type="text" class="form-control" name="print_empresa_instagram" value="<?= e($config['print_empresa_instagram']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Texto de rodapé da impressão</label>
          <input type="text" class="form-control" name="print_rodape_texto" value="<?= e($config['print_rodape_texto']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label d-block">Segunda via automática para crediário</label>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" role="switch" name="print_crediario_segunda_via" id="segundaVia" value="1" <?= $config['print_crediario_segunda_via'] === '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="segundaVia">Ativar segunda via para assinatura do cliente</label>
          </div>
        </div>
      </div>

      <div class="mt-4">
        <button class="btn btn-primary">Salvar configurações</button>
      </div>
    </form>
  </div>
</div>

<?php renderFooter(); ?>
