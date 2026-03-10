<?php
require_once __DIR__ . '/../includes/auth.php'; requireLogin();
$stmt=db()->prepare('INSERT INTO despesas (categoria,valor,data) VALUES (?,?,?)');
$stmt->execute([$_POST['categoria'],$_POST['valor'],$_POST['data']]);
redirect('pages/financeiro.php');
