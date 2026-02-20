<?php
declare(strict_types=1);

function app_config(): array
{
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $config = [
        'app' => [
            'name' => (string) (getenv('APP_NAME') ?: 'Omniflow LMS'),
            'version' => (string) (getenv('APP_VERSION') ?: 'dev'),
        ],
        'routes' => [
            'home' => '/',
            'users' => '/users',
            'assessments' => '/assessments',
        ],
    ];

    return $config;
}

function config(string $key, mixed $default = null): mixed
{
    $value = app_config();
    $segments = explode('.', $key);

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}
