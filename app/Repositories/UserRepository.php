<?php
declare(strict_types=1);

final class UserRepository
{
    /** @return array<int, array<string, mixed>> */
    public function getUsers(): array
    {
        $statement = db()->query('SELECT id, email FROM users ORDER BY id DESC');

        return $statement->fetchAll();
    }

    public function getUserDetailByID(int $id): ?array
    {
        $statement = db()->prepare('SELECT id, email FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        $result = $statement->fetch();

        return is_array($result) ? $result : null;
    }
}
