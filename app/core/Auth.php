<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: ' . base_url('/login'));
            exit;
        }
    }

    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }

        $userPermissions = $_SESSION['user']['permissions'] ?? [];
        return in_array('*', $userPermissions, true) || in_array($permission, $userPermissions, true);
    }

    public static function requirePermission(string $permission): void
    {
        if (!self::can($permission)) {
            http_response_code(403);
            exit('Acesso negado.');
        }
    }
}
