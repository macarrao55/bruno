<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

session_name((string) ((require __DIR__ . '/../config/config.php')['app']['session_name'] ?? 'mega_lanches_session'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => false,
    'samesite' => 'Lax',
]);
session_start();

require __DIR__ . '/../app/helpers/functions.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);
    if (isset($parts[0])) {
        $parts[0] = strtolower($parts[0]);
    }
    $path = __DIR__ . '/../app/' . implode('/', $parts) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

$router = new App\Core\Router();
require __DIR__ . '/../routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
