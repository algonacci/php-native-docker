<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($uri, PHP_URL_PATH);
$path = Router::normalizePath(is_string($path) ? $path : '/');
$errorController = null;

try {
    if ($path === '/index.php') {
        header('Location: /users', true, 301);
        exit;
    }

    if ($path === '/assessments.php') {
        header('Location: /assessments', true, 301);
        exit;
    }

    $container = app_container();
    $router = app_router();
    $errorController = $container->get(ErrorController::class);

    if (!$errorController instanceof ErrorController) {
        throw new RuntimeException('Failed to resolve ErrorController from container.');
    }

    if ($router->dispatch($method, $path)) {
        exit;
    }

    if ($router->pathExistsForAnotherMethod($path, $method)) {
        $errorController->methodNotAllowed($path, $method);
        exit;
    }

    $errorController->notFound($path);
} catch (Throwable $exception) {
    app_report_exception($exception);

    if ($errorController instanceof ErrorController) {
        try {
            $errorController->serverError($path);
            exit;
        } catch (Throwable $renderException) {
            app_report_exception($renderException);
        }
    }

    http_response_code(500);

    if (app_is_debug()) {
        header('Content-Type: text/plain; charset=utf-8');
        echo (string) $exception;
        exit;
    }

    echo 'Internal Server Error';
    exit;
}
