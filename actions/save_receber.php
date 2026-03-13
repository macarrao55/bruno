<?php
require_once __DIR__ . '/../includes/auth.php'; requireLogin();
$stmt=db()->prepare("INSERT INTO contas_receber (cliente_id,valor,vencimento,status) VALUES (?,?,?,'aberto')");
$stmt->execute([$_POST['cliente_id'],$_POST['valor'],$_POST['vencimento']]);
redirect('pages/financeiro.php');
