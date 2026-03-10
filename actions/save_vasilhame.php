<?php
require_once __DIR__ . '/../includes/auth.php'; requireLogin();
$stmt=db()->prepare('INSERT INTO movimentos_vasilhames (cliente_id,tipo,quantidade,movimento) VALUES (?,?,?,?)');
$stmt->execute([$_POST['cliente_id'],$_POST['tipo'],$_POST['quantidade'],$_POST['movimento']]);
redirect('pages/vasilhames.php');
