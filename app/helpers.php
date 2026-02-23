<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool
{
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    $valid = hash_equals($_SESSION['csrf_token'], $token);

    if ($valid) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $valid;
}
