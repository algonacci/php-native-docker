<?php
declare(strict_types=1);

final class UsersController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ErrorController $errors,
    ) {
    }

    public function getUsers(): void
    {
        $app = app_context();
        $error = null;
        $users = [];

        try {
            $users = $this->users->getUsers();
        } catch (Throwable $exception) {
            app_report_exception($exception);
            $error = app_is_debug()
                ? $exception->getMessage()
                : 'Terjadi kesalahan saat memuat data user.';
        }

        render('pages/users', [
            'pageTitle' => 'User List - ' . $app['name'],
            'databaseName' => $app['database'],
            'users' => $users,
            'error' => $error,
        ]);
    }

    public function getUserDetailByID(string $id): void
    {
        $path = '/users/' . $id;

        if (!$this->isValidPositiveInteger($id)) {
            $this->errors->notFound($path, 'User ID tidak valid.');
            return;
        }

        try {
            $user = $this->users->getUserDetailByID((int) $id);
        } catch (Throwable $exception) {
            app_report_exception($exception);
            $this->errors->serverError($path);
            return;
        }

        if ($user === null) {
            $this->errors->notFound($path, 'User tidak ditemukan.');
            return;
        }

        render('pages/user-detail', [
            'pageTitle' => 'User Detail #' . $id,
            'user' => $user,
        ]);
    }

    private function isValidPositiveInteger(string $value): bool
    {
        return ctype_digit($value) && (int) $value > 0;
    }
}
