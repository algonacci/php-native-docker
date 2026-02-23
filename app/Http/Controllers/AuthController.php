<?php
declare(strict_types=1);

final class AuthController
{
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SECONDS = 300;
    private const LOGIN_LOCK_SECONDS = 900;

    public function __construct(
        private readonly LaravelCmsUserRepository $laravelCmsUsers,
        private readonly ErrorController $errors,
    ) {
    }

    public function showLogin(): void
    {
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

        $throttle = login_throttle_status(
            $email,
            self::LOGIN_MAX_ATTEMPTS,
            self::LOGIN_WINDOW_SECONDS,
            self::LOGIN_LOCK_SECONDS,
        );

        if ($throttle['is_locked']) {
            $_SESSION['error'] = 'Terlalu banyak percobaan login. Coba lagi dalam ' . (int) $throttle['retry_after'] . ' detik.';
            header('Location: /login', true, 302);
            return;
        }

        $laravelCmsUser = $this->laravelCmsUsers->findByEmail($email);

        if ($laravelCmsUser === null) {
            login_throttle_record_failure(
                $email,
                self::LOGIN_MAX_ATTEMPTS,
                self::LOGIN_WINDOW_SECONDS,
                self::LOGIN_LOCK_SECONDS,
            );
            $_SESSION['error'] = 'Email atau password salah.';
            header('Location: /login', true, 302);
            return;
        }

        if (!password_verify($password, $laravelCmsUser['password'])) {
            login_throttle_record_failure(
                $email,
                self::LOGIN_MAX_ATTEMPTS,
                self::LOGIN_WINDOW_SECONDS,
                self::LOGIN_LOCK_SECONDS,
            );
            $_SESSION['error'] = 'Email atau password salah.';
            header('Location: /login', true, 302);
            return;
        }

        session_regenerate_id(true);
        login_throttle_clear($email, self::LOGIN_WINDOW_SECONDS);

        $_SESSION['user_id'] = (int) $laravelCmsUser['id'];
        $_SESSION['user_name'] = $laravelCmsUser['name'];
        $_SESSION['user_email'] = $laravelCmsUser['email'];

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
}
