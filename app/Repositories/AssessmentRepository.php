<?php
declare(strict_types=1);

final class AssessmentRepository
{
    /** @return array<int, array<string, mixed>> */
    public function getAllAssessments(): array
    {
        $statement = db()->query('SELECT id, title, description FROM assessments ORDER BY id DESC');

        return $statement->fetchAll();
    }

    public function getAssessmentDetailByID(int $id): ?array
    {
        $statement = db()->prepare('SELECT id, title, description FROM assessments WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        $result = $statement->fetch();

        return is_array($result) ? $result : null;
    }
}
