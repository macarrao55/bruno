<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['administrador', 'gerente', 'entregador']);

header('Content-Type: application/json; charset=utf-8');

$data = $_GET['data'] ?? date('Y-m-d');
$entregadorId = $_GET['entregador_id'] ?? '';
$status = $_GET['status'] ?? '';
$bairro = trim((string) ($_GET['bairro'] ?? ''));

$where = ['DATE(e.created_at) = :data'];
$params = [':data' => $data];

if ($entregadorId !== '') {
    $where[] = 'e.entregador_id = :entregador_id';
    $params[':entregador_id'] = (int) $entregadorId;
}

if ($status !== '') {
    $where[] = 'e.status = :status';
    $params[':status'] = $status;
}

if ($bairro !== '') {
    $where[] = 'c.bairro = :bairro';
    $params[':bairro'] = $bairro;
}

if (currentUser()['nivel'] === 'entregador') {
    $where[] = 'e.entregador_id = :self_id';
    $params[':self_id'] = (int) currentUser()['id'];
}

$sql = 'SELECT e.*, c.nome cliente, c.telefone, c.rua, c.numero, c.bairro, c.referencia, u.nome entregador
        FROM entregas e
        LEFT JOIN clientes c ON c.id = e.cliente_id
        LEFT JOIN usuarios u ON u.id = e.entregador_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY e.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$summary = [
    'total' => count($rows),
    'aberto' => 0,
    'entregues' => 0,
    'atrasados' => 0,
];

foreach ($rows as $r) {
    if ($r['status'] === 'entregue') {
        $summary['entregues']++;
    } elseif ($r['status'] === 'atrasado') {
        $summary['atrasados']++;
    } elseif ($r['status'] !== 'cancelado') {
        $summary['aberto']++;
    }
}

echo json_encode([
    'items' => $rows,
    'summary' => $summary,
], JSON_UNESCAPED_UNICODE);
