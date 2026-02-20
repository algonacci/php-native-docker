<?php
declare(strict_types=1);

function render(string $view, array $data = [], string $layout = 'layouts/main'): void
{
    $viewPath = __DIR__ . '/../views/' . $view . '.php';
    $layoutPath = __DIR__ . '/../views/' . $layout . '.php';

    if (!is_file($viewPath)) {
        throw new RuntimeException('View not found: ' . $view);
    }

    if (!is_file($layoutPath)) {
        throw new RuntimeException('Layout not found: ' . $layout);
    }

    $defaults = [
        'app' => function_exists('app_context') ? app_context() : [],
    ];

    extract(array_merge($defaults, $data), EXTR_SKIP);

    ob_start();
    require $viewPath;
    $content = (string) ob_get_clean();

    require $layoutPath;
}

function partial(string $partial, array $data = []): void
{
    $partialPath = __DIR__ . '/../views/' . $partial . '.php';

    if (!is_file($partialPath)) {
        throw new RuntimeException('Partial not found: ' . $partial);
    }

    extract($data, EXTR_SKIP);
    require $partialPath;
}
