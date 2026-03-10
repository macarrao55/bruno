<?php
require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function money(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function userNameById(int $id): string
{
    $stmt = db()->prepare('SELECT nome FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row['nome'] ?? 'Desconhecido';
}

function roleLabel(string $nivel): string
{
    $map = [
        'administrador' => 'Administrador',
        'gerente' => 'Gerente',
        'caixa' => 'Caixa',
        'vendedor' => 'Vendedor',
        'entregador' => 'Entregador',
    ];
    return $map[$nivel] ?? ucfirst($nivel);
}
