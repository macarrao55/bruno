<?php

declare(strict_types=1);

function app_config(?string $key = null)
{
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/../../config/config.php';
    }

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;
    foreach ($segments as $segment) {
        if (!isset($value[$segment])) {
            return null;
        }
        $value = $value[$segment];
    }

    return $value;
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) app_config('app.url'), '/');
    $suffix = '/' . ltrim($path, '/');
    return $base . ($path === '' ? '' : $suffix);
}

function csrf_token(): string
{
    $key = (string) app_config('security.csrf_key');
    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION[$key];
}

function csrf_field(): string
{
    $key = (string) app_config('security.csrf_key');
    return '<input type="hidden" name="' . $key . '" value="' . csrf_token() . '">';
}

function validate_csrf(): void
{
    $key = (string) app_config('security.csrf_key');
    $token = $_POST[$key] ?? '';
    $sessionToken = $_SESSION[$key] ?? '';

    if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
        http_response_code(419);
        exit('Token CSRF inválido.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = compact('type', 'message');
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}
