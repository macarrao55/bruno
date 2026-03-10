<?php
require_once __DIR__ . '/../includes/auth.php'; requireLogin();
$stmt=db()->prepare("INSERT INTO contas_pagar (fornecedor,valor,vencimento,status) VALUES (?,?,?,'aberto')");
$stmt->execute([$_POST['fornecedor'],$_POST['valor'],$_POST['vencimento']]);
redirect('pages/financeiro.php');
