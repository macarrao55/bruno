<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function check(): bool { return isset($_SESSION['user']); }
    public static function user(): ?array { return $_SESSION['user'] ?? null; }

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
            setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
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

    public static function hasRole(string|array $roles): bool
    {
        $user = self::user();
        if (!$user) return false;
        $roles = (array)$roles;
        return in_array($user['role'], $roles, true);
    }

    public static function requireRole(string|array $roles): void
    {
        if (!self::hasRole($roles)) {
            http_response_code(403);
            exit('Permissão insuficiente.');
        }
    }
}
