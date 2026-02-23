<?php
declare(strict_types=1);

function app_request_is_https(): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));

    if ($https !== '' && $https !== 'off') {
        return true;
    }

    return $forwardedProto === 'https';
}

function app_apply_security_headers(): void
{
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' || headers_sent()) {
        return;
    }

    header_remove('X-Powered-By');

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' https://cdn.tailwindcss.com 'unsafe-inline' 'unsafe-eval'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data:; "
        . "font-src 'self' data:; "
        . "connect-src 'self'; "
        . "form-action 'self'; "
        . "base-uri 'self'; "
        . "frame-ancestors 'none'; "
        . "object-src 'none'"
    );
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    if (app_request_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function app_bootstrap_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secureCookie = app_request_is_https();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    if ($secureCookie) {
        ini_set('session.cookie_secure', '1');
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

app_bootstrap_session();
app_apply_security_headers();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/Container.php';
require_once __DIR__ . '/Repositories/UserRepository.php';
require_once __DIR__ . '/Repositories/LaravelCmsUserRepository.php';
require_once __DIR__ . '/Repositories/AssessmentRepository.php';
require_once __DIR__ . '/Http/Router.php';
require_once __DIR__ . '/Http/Controllers/ErrorController.php';
require_once __DIR__ . '/Http/Controllers/UsersController.php';
require_once __DIR__ . '/Http/Controllers/AssessmentsController.php';
require_once __DIR__ . '/Http/Controllers/LaravelCmsUsersController.php';
require_once __DIR__ . '/Http/Controllers/AuthController.php';

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

function app_health_status(): array
{
    $databaseStatus = 'ok';
    $redisStatus = function_exists('app_redis_health_status') ? app_redis_health_status() : 'unknown';

    try {
        db()->query('SELECT 1');
    } catch (Throwable $exception) {
        app_report_exception($exception);
        $databaseStatus = 'error';
    }

    $appStatus = 'ok';

    if ($databaseStatus !== 'ok' || $redisStatus === 'error') {
        $appStatus = 'degraded';
    }

    return [
        'status' => $appStatus,
        'database' => $databaseStatus,
        'redis' => $redisStatus,
        'timestamp' => gmdate(DATE_ATOM),
    ];
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
            'laravelCmsUsers' => (string) config('routes.laravelCmsUsers', '/laravel-cms-users'),
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
    $container->singleton(LaravelCmsUserRepository::class, static fn(Container $c): LaravelCmsUserRepository => new LaravelCmsUserRepository());
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
        LaravelCmsUsersController::class,
        static fn(Container $c): LaravelCmsUsersController => new LaravelCmsUsersController(
            $c->get(LaravelCmsUserRepository::class),
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
    $container->singleton(
        AuthController::class,
        static fn(Container $c): AuthController => new AuthController(
            $c->get(LaravelCmsUserRepository::class),
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
    $laravelCmsUsersController = $container->get(LaravelCmsUsersController::class);
    $assessmentsController = $container->get(AssessmentsController::class);
    $authController = $container->get(AuthController::class);

    if (!$usersController instanceof UsersController || !$laravelCmsUsersController instanceof LaravelCmsUsersController || !$assessmentsController instanceof AssessmentsController || !$authController instanceof AuthController) {
        throw new RuntimeException('Failed to resolve controllers from container.');
    }

    $routes = app_context()['routes'] ?? [];
    $homePath = Router::normalizePath((string) ($routes['home'] ?? '/'));
    $usersPath = Router::normalizePath((string) ($routes['users'] ?? '/users'));
    $laravelCmsUsersPath = Router::normalizePath((string) ($routes['laravelCmsUsers'] ?? '/laravel-cms-users'));
    $assessmentsPath = Router::normalizePath((string) ($routes['assessments'] ?? '/assessments'));

    $usersDetailPath = $usersPath === '/' ? '/{id}' : $usersPath . '/{id}';
    $laravelCmsUsersDetailPath = $laravelCmsUsersPath === '/' ? '/{id}' : $laravelCmsUsersPath . '/{id}';
    $assessmentsDetailPath = $assessmentsPath === '/' ? '/{id}' : $assessmentsPath . '/{id}';

    $router = new Router();
    $router->get('/healthz', static function (): void {
        $health = app_health_status();

        http_response_code($health['status'] === 'ok' ? 200 : 503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($health, JSON_UNESCAPED_SLASHES);
    });
    $router->get($homePath, static function () use ($usersPath): void {
        header('Location: ' . $usersPath, true, 302);
    });
    $router->get('/login', [$authController, 'showLogin']);
    $router->post('/login', [$authController, 'login']);
    $router->post('/logout', [$authController, 'logout']);
    $router->get($usersPath, [$usersController, 'getUsers']);
    $router->get($usersDetailPath, [$usersController, 'getUserDetailByID']);
    $router->get($laravelCmsUsersPath, [$laravelCmsUsersController, 'getLaravelCmsUsers']);
    $router->get($laravelCmsUsersDetailPath, [$laravelCmsUsersController, 'getLaravelCmsUserDetailByID']);
    $router->get($assessmentsPath, [$assessmentsController, 'getAllAssessments']);
    $router->get($assessmentsDetailPath, [$assessmentsController, 'getAssessmentDetailByID']);

    return $router;
}

function app_is_authenticated(): bool
{
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function app_require_auth(): void
{
    if (!app_is_authenticated()) {
        header('Location: /login', true, 302);
        exit;
    }
}

error_reporting(E_ALL);
ini_set('display_errors', app_is_debug() ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
