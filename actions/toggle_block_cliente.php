<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if (!tableHasColumn('clientes', 'bloqueado')) {
    redirect('pages/clientes.php');
}

$id = (int) ($_POST['id'] ?? 0);
$sel = db()->prepare('SELECT bloqueado FROM clientes WHERE id = ?');
$sel->execute([$id]);
$row = $sel->fetch();

if ($row) {
    $novo = ((int) $row['bloqueado'] === 1) ? 0 : 1;
    $upd = db()->prepare('UPDATE clientes SET bloqueado = ? WHERE id = ?');
    $upd->execute([$novo, $id]);
}

redirect('pages/clientes.php');
