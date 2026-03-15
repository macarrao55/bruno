<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['administrador', 'gerente']);

$pdo = db();

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $dias = (int) ($_POST['crediario_dias_vencimento'] ?? 30);
    if ($dias < 1 || $dias > 365) {
        throw new RuntimeException('Dias de vencimento do crediário deve estar entre 1 e 365.');
    }

    $formasPagamento = trim((string) ($_POST['formas_pagamento'] ?? ''));
    if ($formasPagamento === '') {
        $formasPagamento = 'Dinheiro, Pix, Cartão, Crediário, Cheque';
    }

    $recebimentoOpcoesRaw = trim((string) ($_POST['recebimento_opcoes'] ?? 'recebido,na_entrega'));
    $recebimentoTokens = array_values(array_filter(array_map('trim', explode(',', $recebimentoOpcoesRaw))));
    $recebimentoSanitizado = [];
    foreach ($recebimentoTokens as $token) {
        $norm = normalizeTextSimple($token);
        if ($norm === 'recebido' && !in_array('recebido', $recebimentoSanitizado, true)) {
            $recebimentoSanitizado[] = 'recebido';
        }
        if ($norm === 'na_entrega' && !in_array('na_entrega', $recebimentoSanitizado, true)) {
            $recebimentoSanitizado[] = 'na_entrega';
        }
    }
    if (!$recebimentoSanitizado) {
        $recebimentoSanitizado = ['recebido', 'na_entrega'];
    }

    $recebimentoPadrao = normalizeTextSimple((string) ($_POST['recebimento_padrao'] ?? 'recebido'));
    if (!in_array($recebimentoPadrao, $recebimentoSanitizado, true)) {
        $recebimentoPadrao = $recebimentoSanitizado[0];
    }

    $values = [
        'app_nome' => trim((string) ($_POST['app_nome'] ?? APP_NAME)),
        'print_empresa_nome' => trim((string) ($_POST['print_empresa_nome'] ?? PRINT_EMPRESA_NOME)),
        'print_empresa_telefone' => trim((string) ($_POST['print_empresa_telefone'] ?? PRINT_EMPRESA_TELEFONE)),
        'print_empresa_instagram' => trim((string) ($_POST['print_empresa_instagram'] ?? PRINT_EMPRESA_INSTAGRAM)),
        'print_rodape_texto' => trim((string) ($_POST['print_rodape_texto'] ?? PRINT_RODAPE_TEXTO)),
        'print_crediario_segunda_via' => !empty($_POST['print_crediario_segunda_via']) ? '1' : '0',
        'crediario_dias_vencimento' => (string) $dias,
        'formas_pagamento' => $formasPagamento,
        'recebimento_opcoes' => implode(',', $recebimentoSanitizado),
        'recebimento_padrao' => $recebimentoPadrao,
    ];

    $stmt = $pdo->prepare('INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
    foreach ($values as $key => $value) {
        $stmt->execute([$key, $value]);
    }

    redirect('pages/configuracoes.php?ok=' . urlencode('Configurações salvas com sucesso.'));
} catch (Throwable $e) {
    redirect('pages/configuracoes.php?erro=' . urlencode('Falha ao salvar configurações: ' . $e->getMessage()));
}
