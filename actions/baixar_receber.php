<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['administrador', 'gerente', 'caixa']);

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('pages/contas_receber.php?erro=' . urlencode('Conta inválida.'));
}

$stmt = db()->prepare("UPDATE contas_receber SET status = 'pago' WHERE id = ?");
$stmt->execute([$id]);

redirect('pages/contas_receber.php?ok=' . urlencode('Conta baixada com sucesso.'));
