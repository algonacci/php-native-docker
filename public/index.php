<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($uri, PHP_URL_PATH);
$path = Router::normalizePath(is_string($path) ? $path : '/');

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
