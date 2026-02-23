<?php
declare(strict_types=1);

final class LaravelCmsUsersController
{
    public function __construct(
        private readonly LaravelCmsUserRepository $laravelCmsUsers,
        private readonly ErrorController $errors,
    ) {
    }

    public function getLaravelCmsUsers(): void
    {
        app_require_auth();

        $app = app_context();
        $error = null;
        $laravelCmsUsers = [];

        try {
            $laravelCmsUsers = $this->laravelCmsUsers->getLaravelCmsUsers();
        } catch (Throwable $exception) {
            app_report_exception($exception);
            $error = app_is_debug()
                ? $exception->getMessage()
                : 'Terjadi kesalahan saat memuat data Laravel CMS users.';
        }

        render('pages/laravel-cms-users', [
            'pageTitle' => 'Laravel CMS Users - ' . $app['name'],
            'laravelCmsUsers' => $laravelCmsUsers,
            'error' => $error,
        ]);
    }

    public function getLaravelCmsUserDetailByID(string $id): void
    {
        app_require_auth();

        $path = '/laravel-cms-users/' . $id;

        if (!$this->isValidPositiveInteger($id)) {
            $this->errors->notFound($path, 'Laravel CMS User ID tidak valid.');
            return;
        }

        try {
            $laravelCmsUser = $this->laravelCmsUsers->getLaravelCmsUserDetailByID((int) $id);
        } catch (Throwable $exception) {
            app_report_exception($exception);
            $this->errors->serverError($path);
            return;
        }

        if ($laravelCmsUser === null) {
            $this->errors->notFound($path, 'Laravel CMS User tidak ditemukan.');
            return;
        }

        render('pages/laravel-cms-user-detail', [
            'pageTitle' => 'Laravel CMS User Detail #' . $id,
            'laravelCmsUser' => $laravelCmsUser,
        ]);
    }

    private function isValidPositiveInteger(string $value): bool
    {
        return ctype_digit($value) && (int) $value > 0;
    }
}
