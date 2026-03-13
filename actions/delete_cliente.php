<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = (int) ($_POST['id'] ?? 0);

$temVinculo = false;
$checks = [
    'SELECT COUNT(*) c FROM vendas WHERE cliente_id = ?',
    'SELECT COUNT(*) c FROM contas_receber WHERE cliente_id = ?',
    'SELECT COUNT(*) c FROM entregas WHERE cliente_id = ?',
    'SELECT COUNT(*) c FROM movimentos_vasilhames WHERE cliente_id = ?',
];

foreach ($checks as $sql) {
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    if ((int) ($stmt->fetch()['c'] ?? 0) > 0) {
        $temVinculo = true;
        break;
    }
}

if ($temVinculo && tableHasColumn('clientes', 'bloqueado')) {
    $blk = db()->prepare('UPDATE clientes SET bloqueado = 1 WHERE id = ?');
    $blk->execute([$id]);
} elseif (!$temVinculo) {
    $del = db()->prepare('DELETE FROM clientes WHERE id = ?');
    $del->execute([$id]);
}

redirect('pages/clientes.php');
