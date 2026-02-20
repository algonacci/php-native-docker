<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) (getenv('DB_HOST') ?: '');
    $port = (string) (getenv('DB_PORT') ?: '5432');
    $database = (string) (getenv('DB_DATABASE') ?: '');
    $username = (string) (getenv('DB_USERNAME') ?: '');
    $password = (string) (getenv('DB_PASSWORD') ?: '');

    if ($host === '' || $database === '' || $username === '') {
        throw new RuntimeException('Database configuration is incomplete.');
    }

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $exception) {
        throw new RuntimeException('Failed to connect to database.', 0, $exception);
    }

    return $pdo;
}
