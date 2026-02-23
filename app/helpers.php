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

function env_bool(string $name, bool $default = false): bool
{
    $value = getenv($name);

    if ($value === false) {
        return $default;
    }

    $normalized = strtolower(trim((string) $value));

    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }

    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }

    return $default;
}

/**
 * @return Redis|null
 */
function app_redis_client(): mixed
{
    static $resolved = false;
    static $client = null;
    static $reportedMissingExtension = false;

    if ($resolved) {
        return $client;
    }

    $resolved = true;

    if (!env_bool('REDIS_ENABLED', false)) {
        $client = null;
        return $client;
    }

    if (!class_exists('Redis')) {
        if (!$reportedMissingExtension) {
            error_log('REDIS_ENABLED=true but ext-redis is not installed. Falling back to file throttle.');
            $reportedMissingExtension = true;
        }
        $client = null;
        return $client;
    }

    $host = (string) (getenv('REDIS_HOST') ?: '127.0.0.1');
    $port = (int) (getenv('REDIS_PORT') ?: 6379);
    $db = max(0, (int) (getenv('REDIS_DB') ?: 0));
    $password = (string) (getenv('REDIS_PASSWORD') ?: '');
    $username = (string) (getenv('REDIS_USERNAME') ?: '');
    $timeout = max(0.1, (float) (getenv('REDIS_TIMEOUT') ?: 2.0));
    $readTimeout = max(0.1, (float) (getenv('REDIS_READ_TIMEOUT') ?: 2.0));

    try {
        $redis = new Redis();
        $connected = $redis->connect($host, $port, $timeout, null, 0, $readTimeout);

        if ($connected !== true) {
            throw new RuntimeException('Unable to connect to Redis server.');
        }

        if ($username !== '' || $password !== '') {
            if ($username !== '') {
                $redis->auth([$username, $password]);
            } else {
                $redis->auth($password);
            }
        }

        if ($db > 0) {
            $redis->select($db);
        }

        $redis->ping();
        $client = $redis;
    } catch (Throwable $exception) {
        app_report_exception($exception);
        $client = null;
    }

    return $client;
}

function app_redis_health_status(): string
{
    if (!env_bool('REDIS_ENABLED', false)) {
        return 'disabled';
    }

    $redis = app_redis_client();

    if (!$redis instanceof Redis) {
        return 'error';
    }

    try {
        $redis->ping();
        return 'ok';
    } catch (Throwable $exception) {
        app_report_exception($exception);
        return 'error';
    }
}

function login_throttle_backend(): string
{
    return app_redis_client() instanceof Redis ? 'redis' : 'file';
}

function app_redis_key_prefix(): string
{
    $rawPrefix = trim((string) (getenv('REDIS_PREFIX') ?: 'php-native:'));

    if ($rawPrefix === '') {
        return 'php-native:';
    }

    return rtrim($rawPrefix, ':') . ':';
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
    $redisResult = login_throttle_status_redis($email, $maxAttempts, $windowSeconds, $lockSeconds);

    if (is_array($redisResult)) {
        return $redisResult;
    }

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
    if (login_throttle_record_failure_redis($email, $maxAttempts, $windowSeconds, $lockSeconds)) {
        return;
    }

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
    if (login_throttle_clear_redis($email)) {
        return;
    }

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
 * @return array{0:string,1:string}
 */
function login_throttle_redis_keys(string $email): array
{
    $key = login_throttle_key($email);
    $prefix = app_redis_key_prefix() . 'auth_throttle:';

    return [
        $prefix . 'attempts:' . $key,
        $prefix . 'lock:' . $key,
    ];
}

/**
 * @return array{is_locked:bool,retry_after:int}|null
 */
function login_throttle_status_redis(
    string $email,
    int $maxAttempts,
    int $windowSeconds,
    int $lockSeconds,
): ?array {
    $redis = app_redis_client();

    if (!$redis instanceof Redis) {
        return null;
    }

    try {
        [$attemptsKey, $lockKey] = login_throttle_redis_keys($email);
        $lockTtl = (int) $redis->ttl($lockKey);

        if ($lockTtl > 0) {
            return [
                'is_locked' => true,
                'retry_after' => $lockTtl,
            ];
        }

        if ($lockTtl === -1) {
            $redis->expire($lockKey, $lockSeconds);

            return [
                'is_locked' => true,
                'retry_after' => $lockSeconds,
            ];
        }

        $attemptsRaw = $redis->get($attemptsKey);
        $attempts = is_numeric($attemptsRaw) ? (int) $attemptsRaw : 0;

        if ($attempts >= $maxAttempts) {
            $redis->setex($lockKey, $lockSeconds, '1');
            $redis->del($attemptsKey);

            return [
                'is_locked' => true,
                'retry_after' => $lockSeconds,
            ];
        }

        if ($attempts > 0 && $redis->ttl($attemptsKey) < 0) {
            $redis->expire($attemptsKey, $windowSeconds);
        }

        return [
            'is_locked' => false,
            'retry_after' => 0,
        ];
    } catch (Throwable $exception) {
        app_report_exception($exception);
        return null;
    }
}

function login_throttle_record_failure_redis(
    string $email,
    int $maxAttempts,
    int $windowSeconds,
    int $lockSeconds,
): bool {
    $redis = app_redis_client();

    if (!$redis instanceof Redis) {
        return false;
    }

    try {
        [$attemptsKey, $lockKey] = login_throttle_redis_keys($email);
        $lockTtl = (int) $redis->ttl($lockKey);

        if ($lockTtl > 0) {
            return true;
        }

        if ($lockTtl === -1) {
            $redis->expire($lockKey, $lockSeconds);
            return true;
        }

        $attempts = (int) $redis->incr($attemptsKey);

        if ($attempts === 1) {
            $redis->expire($attemptsKey, $windowSeconds);
        }

        if ($attempts >= $maxAttempts) {
            $redis->setex($lockKey, $lockSeconds, '1');
            $redis->del($attemptsKey);
        }

        return true;
    } catch (Throwable $exception) {
        app_report_exception($exception);
        return false;
    }
}

function login_throttle_clear_redis(string $email): bool
{
    $redis = app_redis_client();

    if (!$redis instanceof Redis) {
        return false;
    }

    try {
        [$attemptsKey, $lockKey] = login_throttle_redis_keys($email);
        $redis->del($attemptsKey, $lockKey);
        return true;
    } catch (Throwable $exception) {
        app_report_exception($exception);
        return false;
    }
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
