<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/view.php';
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

error_reporting(E_ALL);
ini_set('display_errors', app_is_debug() ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
