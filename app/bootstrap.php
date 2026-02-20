<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/Container.php';
require_once __DIR__ . '/Repositories/UserRepository.php';
require_once __DIR__ . '/Repositories/AssessmentRepository.php';
require_once __DIR__ . '/Http/Router.php';
require_once __DIR__ . '/Http/Controllers/ErrorController.php';
require_once __DIR__ . '/Http/Controllers/UsersController.php';
require_once __DIR__ . '/Http/Controllers/AssessmentsController.php';

function app_is_debug(): bool
{
    static $debug = null;

    if (is_bool($debug)) {
        return $debug;
    }

    $raw = strtolower((string) (getenv('APP_DEBUG') ?: '0'));
    $debug = in_array($raw, ['1', 'true', 'yes', 'on'], true);

    return $debug;
}

function app_report_exception(Throwable $exception): void
{
    error_log((string) $exception);
}

function app_context(): array
{
    static $context = null;

    if (is_array($context)) {
        return $context;
    }

    $context = [
        'name' => (string) config('app.name', 'Omniflow LMS'),
        'version' => (string) config('app.version', 'dev'),
        'database' => (string) (getenv('DB_DATABASE') ?: '-'),
        'routes' => [
            'home' => (string) config('routes.home', '/'),
            'users' => (string) config('routes.users', '/users'),
            'assessments' => (string) config('routes.assessments', '/assessments'),
        ],
    ];

    return $context;
}

function app_container(): Container
{
    static $container = null;

    if ($container instanceof Container) {
        return $container;
    }

    $container = new Container();

    $container->singleton(UserRepository::class, static fn(Container $c): UserRepository => new UserRepository());
    $container->singleton(AssessmentRepository::class, static fn(Container $c): AssessmentRepository => new AssessmentRepository());
    $container->singleton(ErrorController::class, static fn(Container $c): ErrorController => new ErrorController());
    $container->singleton(
        UsersController::class,
        static fn(Container $c): UsersController => new UsersController(
            $c->get(UserRepository::class),
            $c->get(ErrorController::class),
        )
    );
    $container->singleton(
        AssessmentsController::class,
        static fn(Container $c): AssessmentsController => new AssessmentsController(
            $c->get(AssessmentRepository::class),
            $c->get(ErrorController::class),
        )
    );

    return $container;
}

function app_router(): Router
{
    static $router = null;

    if ($router instanceof Router) {
        return $router;
    }

    $container = app_container();
    $usersController = $container->get(UsersController::class);
    $assessmentsController = $container->get(AssessmentsController::class);

    if (!$usersController instanceof UsersController || !$assessmentsController instanceof AssessmentsController) {
        throw new RuntimeException('Failed to resolve controllers from container.');
    }

    $routes = app_context()['routes'] ?? [];
    $homePath = Router::normalizePath((string) ($routes['home'] ?? '/'));
    $usersPath = Router::normalizePath((string) ($routes['users'] ?? '/users'));
    $assessmentsPath = Router::normalizePath((string) ($routes['assessments'] ?? '/assessments'));

    $usersDetailPath = $usersPath === '/' ? '/{id}' : $usersPath . '/{id}';
    $assessmentsDetailPath = $assessmentsPath === '/' ? '/{id}' : $assessmentsPath . '/{id}';

    $router = new Router();
    $router->get($homePath, static function () use ($usersPath): void {
        header('Location: ' . $usersPath, true, 302);
    });
    $router->get($usersPath, [$usersController, 'getUsers']);
    $router->get($usersDetailPath, [$usersController, 'getUserDetailByID']);
    $router->get($assessmentsPath, [$assessmentsController, 'getAllAssessments']);
    $router->get($assessmentsDetailPath, [$assessmentsController, 'getAssessmentDetailByID']);

    return $router;
}

error_reporting(E_ALL);
ini_set('display_errors', app_is_debug() ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
