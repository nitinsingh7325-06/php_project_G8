<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $name, array $data = [], ?string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $name) . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View not found: {$name}";
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout) {
            $layoutFile = dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function partial(string $name, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/partials/' . $name . '.php';
    }
}
