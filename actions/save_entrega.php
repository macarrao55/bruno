<?php
require_once __DIR__ . '/../includes/auth.php'; requireLogin();
$valor=(float)($_POST['valor_pedido']??0);
$comissao=$valor*0.03; // comissão automática simples
$stmt=db()->prepare('INSERT INTO entregas (pedido,cliente_id,entregador_id,status,valor_pedido,comissao) VALUES (?,?,?,?,?,?)');
$stmt->execute([$_POST['pedido'],$_POST['cliente_id'],$_POST['entregador_id'],$_POST['status'],$valor,$comissao]);
redirect('pages/entregas.php');
