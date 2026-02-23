<?php
declare(strict_types=1);

final class AuthController
{
    private const DEFAULT_LOGIN_MAX_ATTEMPTS = 5;
    private const DEFAULT_LOGIN_WINDOW_SECONDS = 300;
    private const DEFAULT_LOGIN_LOCK_SECONDS = 900;
    private const AUTH_FAILURE_DELAY_MICROSECONDS = 250000;
    private const PASSWORD_VERIFY_PLACEHOLDER_HASH = '$2y$12$C.JA7Msq0gagdfn2lChpoOZ1CE48VN4iReA8mHI49MfDQpbRyRBGa';

    public function __construct(
        private readonly LaravelCmsUserRepository $laravelCmsUsers,
        private readonly ErrorController $errors,
    ) {
    }

    public function showLogin(): void
    {
        if (app_is_authenticated()) {
            header('Location: /users', true, 302);
            return;
        }

        render('pages/login', [
            'pageTitle' => 'Login',
        ]);
    }

    public function login(): void
    {
        $token = (string) ($_POST['_token'] ?? '');

        if (!verify_csrf_token($token)) {
            $_SESSION['error'] = 'CSRF token invalid.';
            header('Location: /login', true, 302);
            return;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $_SESSION['error'] = 'Email dan password wajib diisi.';
            header('Location: /login', true, 302);
            return;
        }

        $maxAttempts = $this->loginMaxAttempts();
        $windowSeconds = $this->loginWindowSeconds();
        $lockSeconds = $this->loginLockSeconds();

        $throttle = login_throttle_status(
            $email,
            $maxAttempts,
            $windowSeconds,
            $lockSeconds,
        );

        if ($throttle['is_locked']) {
            $_SESSION['error'] = 'Terlalu banyak percobaan login. Coba lagi dalam ' . (int) $throttle['retry_after'] . ' detik.';
            header('Location: /login', true, 302);
            return;
        }

        $laravelCmsUser = $this->laravelCmsUsers->findByEmail($email);
        $passwordHash = self::PASSWORD_VERIFY_PLACEHOLDER_HASH;

        if (is_array($laravelCmsUser) && isset($laravelCmsUser['password']) && is_string($laravelCmsUser['password']) && $laravelCmsUser['password'] !== '') {
            $passwordHash = $laravelCmsUser['password'];
        }

        $passwordMatches = password_verify($password, $passwordHash);

        if ($laravelCmsUser === null || !$passwordMatches) {
            login_throttle_record_failure(
                $email,
                $maxAttempts,
                $windowSeconds,
                $lockSeconds,
            );
            $this->sleepOnAuthFailure();
            $_SESSION['error'] = 'Email atau password salah.';
            header('Location: /login', true, 302);
            return;
        }

        session_regenerate_id(true);
        login_throttle_clear($email, $windowSeconds);

        $_SESSION['user_id'] = (int) $laravelCmsUser['id'];
        $_SESSION['user_name'] = $laravelCmsUser['name'];
        $_SESSION['user_email'] = $laravelCmsUser['email'];
        $_SESSION['last_login_at'] = time();

        unset($_SESSION['error']);

        header('Location: /users', true, 302);
    }

    public function logout(): void
    {
        $token = (string) ($_POST['_token'] ?? '');

        if (!verify_csrf_token($token)) {
            $_SESSION['error'] = 'CSRF token invalid.';
            header('Location: /users', true, 302);
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool) ($params['secure'] ?? false),
                (bool) ($params['httponly'] ?? true),
            );
        }

        session_destroy();

        header('Location: /login', true, 302);
    }

    private function loginMaxAttempts(): int
    {
        return max(1, (int) (getenv('AUTH_LOGIN_MAX_ATTEMPTS') ?: self::DEFAULT_LOGIN_MAX_ATTEMPTS));
    }

    private function loginWindowSeconds(): int
    {
        return max(60, (int) (getenv('AUTH_LOGIN_WINDOW_SECONDS') ?: self::DEFAULT_LOGIN_WINDOW_SECONDS));
    }

    private function loginLockSeconds(): int
    {
        return max(60, (int) (getenv('AUTH_LOGIN_LOCK_SECONDS') ?: self::DEFAULT_LOGIN_LOCK_SECONDS));
    }

    private function sleepOnAuthFailure(): void
    {
        $jitter = 0;

        try {
            $jitter = random_int(0, 150000);
        } catch (Throwable) {
            $jitter = 0;
        }

        usleep(self::AUTH_FAILURE_DELAY_MICROSECONDS + $jitter);
    }
}
