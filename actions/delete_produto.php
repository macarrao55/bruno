<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = (int) ($_POST['id'] ?? 0);

$checks = [
    'SELECT COUNT(*) c FROM itens_venda WHERE produto_id = ?',
    'SELECT COUNT(*) c FROM compras WHERE produto_id = ?',
];

foreach ($checks as $sql) {
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    if ((int) ($stmt->fetch()['c'] ?? 0) > 0) {
        redirect('pages/produtos.php?msg=linked');
    }
}

$del = db()->prepare('DELETE FROM produtos WHERE id = ?');
$del->execute([$id]);

redirect('pages/produtos.php?msg=deleted');
