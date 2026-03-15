<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, string $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    private function add(string $method, string $path, string $handler, array $middlewares): void
    {
        $this->routes[$method][$path] = compact('handler', 'middlewares');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
            $path = rtrim($path, '/') ?: '/';
        }

        $route = $this->routes[$method][$path] ?? null;
        if (!$route) {
            http_response_code(404);
            echo 'Rota não encontrada';
            return;
        }

        foreach ($route['middlewares'] as $middleware) {
            if (is_callable($middleware)) {
                $middleware();
            }
        }

        [$controller, $action] = explode('@', $route['handler']);
        $controllerClass = 'App\\Controllers\\' . $controller;
        $instance = new $controllerClass();
        $instance->{$action}();
    }
}
