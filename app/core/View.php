<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        $layoutPath = __DIR__ . '/../views/' . $layout . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(404);
            exit('View não encontrada: ' . htmlspecialchars($view));
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require $layoutPath;
    }

    public static function partial(string $view, array $data = []): void
    {
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        extract($data, EXTR_SKIP);
        require $viewPath;
    }
}
