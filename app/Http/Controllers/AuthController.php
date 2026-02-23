<?php
declare(strict_types=1);

final class AuthController
{
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

        $laravelCmsUser = $this->laravelCmsUsers->findByEmail($email);

        if ($laravelCmsUser === null) {
            $_SESSION['error'] = 'Email atau password salah.';
            header('Location: /login', true, 302);
            return;
        }

        if (!password_verify($password, $laravelCmsUser['password'])) {
            $_SESSION['error'] = 'Email atau password salah.';
            header('Location: /login', true, 302);
            return;
        }

        $_SESSION['user_id'] = (int) $laravelCmsUser['id'];
        $_SESSION['user_name'] = $laravelCmsUser['name'];
        $_SESSION['user_email'] = $laravelCmsUser['email'];

        unset($_SESSION['error']);

        header('Location: /users', true, 302);
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();

        header('Location: /login', true, 302);
    }
}
