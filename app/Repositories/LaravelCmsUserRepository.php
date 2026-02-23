<?php
declare(strict_types=1);

final class LaravelCmsUserRepository
{
    /** @return array<int, array<string, mixed>> */
    public function getLaravelCmsUsers(): array
    {
        $statement = db()->query('SELECT id, name, email FROM laravel_cms_users WHERE deleted_at IS NULL ORDER BY id DESC');

        return $statement->fetchAll();
    }

    public function getLaravelCmsUserDetailByID(int $id): ?array
    {
        $statement = db()->prepare('SELECT id, name, email FROM laravel_cms_users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['id' => $id]);

        $result = $statement->fetch();

        return is_array($result) ? $result : null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = db()->prepare('SELECT id, name, email, password FROM laravel_cms_users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['email' => $email]);

        $result = $statement->fetch();

        return is_array($result) ? $result : null;
    }
}
