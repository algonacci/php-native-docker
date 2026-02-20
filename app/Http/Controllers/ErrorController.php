<?php
declare(strict_types=1);

final class ErrorController
{
    public function notFound(string $path, string $message = 'Halaman tidak ditemukan.'): void
    {
        http_response_code(404);

        render('pages/not-found', [
            'pageTitle' => '404 Not Found',
            'statusCode' => 404,
            'message' => $message,
            'requestedPath' => $path,
        ]);
    }

    public function methodNotAllowed(string $path, string $method): void
    {
        http_response_code(405);

        render('pages/not-found', [
            'pageTitle' => '405 Method Not Allowed',
            'statusCode' => 405,
            'message' => 'Method ' . $method . ' tidak diizinkan untuk endpoint ini.',
            'requestedPath' => $path,
        ]);
    }

    public function serverError(string $path): void
    {
        http_response_code(500);

        render('pages/not-found', [
            'pageTitle' => '500 Internal Server Error',
            'statusCode' => 500,
            'message' => 'Terjadi kesalahan internal.',
            'requestedPath' => $path,
        ]);
    }
}
