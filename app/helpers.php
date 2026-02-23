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

function client_ip(): string
{
    $forwardedFor = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');

    if ($forwardedFor !== '') {
        $parts = explode(',', $forwardedFor);
        $candidate = trim($parts[0] ?? '');

        if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
            return $candidate;
        }
    }

    $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    if (filter_var($remoteAddress, FILTER_VALIDATE_IP) !== false) {
        return $remoteAddress;
    }

    return 'unknown';
}

/**
 * @return array{is_locked:bool,retry_after:int}
 */
function login_throttle_status(
    string $email,
    int $maxAttempts = 5,
    int $windowSeconds = 300,
    int $lockSeconds = 900,
): array {
    return login_throttle_mutate(
        static function (array &$state) use ($email, $maxAttempts, $windowSeconds, $lockSeconds): array {
            $now = time();
            $key = login_throttle_key($email);
            login_throttle_prune_state($state, $now, $windowSeconds);

            $entry = $state['keys'][$key] ?? [
                'attempts' => [],
                'lock_until' => 0,
            ];

            $attempts = login_throttle_filter_attempts($entry['attempts'] ?? [], $now, $windowSeconds);
            $lockUntil = (int) ($entry['lock_until'] ?? 0);

            if ($lockUntil > $now) {
                return [
                    'is_locked' => true,
                    'retry_after' => $lockUntil - $now,
                ];
            }

            if (count($attempts) >= $maxAttempts) {
                $lockUntil = $now + $lockSeconds;
                $state['keys'][$key] = [
                    'attempts' => [],
                    'lock_until' => $lockUntil,
                ];

                return [
                    'is_locked' => true,
                    'retry_after' => $lockSeconds,
                ];
            }

            $state['keys'][$key] = [
                'attempts' => $attempts,
                'lock_until' => 0,
            ];

            return [
                'is_locked' => false,
                'retry_after' => 0,
            ];
        }
    );
}

function login_throttle_record_failure(
    string $email,
    int $maxAttempts = 5,
    int $windowSeconds = 300,
    int $lockSeconds = 900,
): void {
    login_throttle_mutate(
        static function (array &$state) use ($email, $maxAttempts, $windowSeconds, $lockSeconds): void {
            $now = time();
            $key = login_throttle_key($email);
            login_throttle_prune_state($state, $now, $windowSeconds);

            $entry = $state['keys'][$key] ?? [
                'attempts' => [],
                'lock_until' => 0,
            ];
            $lockUntil = (int) ($entry['lock_until'] ?? 0);

            if ($lockUntil > $now) {
                return;
            }

            $attempts = login_throttle_filter_attempts($entry['attempts'] ?? [], $now, $windowSeconds);
            $attempts[] = $now;

            if (count($attempts) >= $maxAttempts) {
                $state['keys'][$key] = [
                    'attempts' => [],
                    'lock_until' => $now + $lockSeconds,
                ];
                return;
            }

            $state['keys'][$key] = [
                'attempts' => $attempts,
                'lock_until' => 0,
            ];
        }
    );
}

function login_throttle_clear(
    string $email,
    int $windowSeconds = 300,
): void {
    login_throttle_mutate(
        static function (array &$state) use ($email, $windowSeconds): void {
            $now = time();
            $key = login_throttle_key($email);
            login_throttle_prune_state($state, $now, $windowSeconds);

            unset($state['keys'][$key]);
        }
    );
}

function login_throttle_key(string $email): string
{
    return hash('sha256', strtolower(trim($email)) . '|' . client_ip());
}

/**
 * @param array<int, mixed> $attempts
 * @return array<int, int>
 */
function login_throttle_filter_attempts(array $attempts, int $now, int $windowSeconds): array
{
    $threshold = $now - $windowSeconds;
    $filtered = [];

    foreach ($attempts as $attempt) {
        if (!is_int($attempt) && !is_numeric($attempt)) {
            continue;
        }

        $timestamp = (int) $attempt;

        if ($timestamp >= $threshold) {
            $filtered[] = $timestamp;
        }
    }

    return $filtered;
}

function login_throttle_prune_state(array &$state, int $now, int $windowSeconds): void
{
    $keys = $state['keys'] ?? [];

    if (!is_array($keys)) {
        $state['keys'] = [];
        return;
    }

    foreach ($keys as $key => $entry) {
        if (!is_array($entry)) {
            unset($state['keys'][$key]);
            continue;
        }

        $attempts = login_throttle_filter_attempts((array) ($entry['attempts'] ?? []), $now, $windowSeconds);
        $lockUntil = (int) ($entry['lock_until'] ?? 0);

        if ($lockUntil <= $now && $attempts === []) {
            unset($state['keys'][$key]);
            continue;
        }

        $state['keys'][$key] = [
            'attempts' => $attempts,
            'lock_until' => $lockUntil,
        ];
    }
}

function login_throttle_mutate(callable $mutator): mixed
{
    $path = __DIR__ . '/../logs/login-throttle.json';
    $directory = dirname($path);

    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create throttle storage directory.');
    }

    $handle = fopen($path, 'c+');

    if ($handle === false) {
        throw new RuntimeException('Unable to open throttle storage.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Unable to lock throttle storage.');
        }

        rewind($handle);
        $raw = stream_get_contents($handle);
        $decoded = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : null;
        $state = ['keys' => []];

        if (is_array($decoded) && isset($decoded['keys']) && is_array($decoded['keys'])) {
            $state = $decoded;
        }

        $result = $mutator($state);
        $payload = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new RuntimeException('Unable to encode throttle storage.');
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $payload);
        fflush($handle);

        flock($handle, LOCK_UN);

        return $result;
    } finally {
        fclose($handle);
    }
}
