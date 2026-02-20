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

$errorController = new ErrorController();
$usersController = new UsersController(new UserRepository(), $errorController);
$assessmentsController = new AssessmentsController(new AssessmentRepository(), $errorController);

$router = new Router();
$router->get('/', static function (): void {
    header('Location: /users', true, 302);
});
$router->get('/users', [$usersController, 'getUsers']);
$router->get('/users/{id}', [$usersController, 'getUserDetailByID']);
$router->get('/assessments', [$assessmentsController, 'getAllAssessments']);
$router->get('/assessments/{id}', [$assessmentsController, 'getAssessmentDetailByID']);

if ($router->dispatch($method, $path)) {
    exit;
}

if ($router->pathExistsForAnotherMethod($path, $method)) {
    $errorController->methodNotAllowed($path, $method);
    exit;
}

$errorController->notFound($path);
