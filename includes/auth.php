<?php
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLogged(): bool
{
    return !empty($_SESSION['user']);
}

function requireLogin(): void
{
    if (!isLogged()) {
        redirect('login.php');
    }
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function hasRole(array $allowed): bool
{
    $user = currentUser();
    if (!$user) {
        return false;
    }
    return in_array($user['nivel'], $allowed, true);
}

function requireRole(array $allowed): void
{
    if (!hasRole($allowed)) {
        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }
}
