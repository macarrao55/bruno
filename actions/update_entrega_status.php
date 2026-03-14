<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['administrador', 'gerente', 'entregador']);

header('Content-Type: application/json; charset=utf-8');

$id = (int) ($_POST['id'] ?? 0);
$status = trim((string) ($_POST['status'] ?? ''));
$lat = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
$lng = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;

$valid = ['preparando', 'em rota', 'entregue', 'cancelado', 'atrasado'];
if ($id <= 0 || !in_array($status, $valid, true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Dados inválidos']);
    exit;
}

$check = db()->prepare('SELECT entregador_id FROM entregas WHERE id = ?');
$check->execute([$id]);
$row = $check->fetch();
if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Entrega não encontrada']);
    exit;
}

if (currentUser()['nivel'] === 'entregador' && (int) $row['entregador_id'] !== (int) currentUser()['id']) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Sem permissão']);
    exit;
}

$startedAt = $status === 'em rota' ? date('Y-m-d H:i:s') : null;
$deliveredAt = $status === 'entregue' ? date('Y-m-d H:i:s') : null;

$pdo = db();
$sql = 'UPDATE entregas SET status = :status,
        started_at = COALESCE(:started_at, started_at),
        delivered_at = COALESCE(:delivered_at, delivered_at),
        latitude = COALESCE(:lat, latitude),
        longitude = COALESCE(:lng, longitude)
        WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':status' => $status,
    ':started_at' => $startedAt,
    ':delivered_at' => $deliveredAt,
    ':lat' => $lat,
    ':lng' => $lng,
    ':id' => $id,
]);

echo json_encode(['ok' => true]);
