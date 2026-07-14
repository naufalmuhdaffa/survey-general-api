<?php

declare(strict_types=1);

namespace App\Features\Survey\Create;

use PDO;
use App\Database;

final class CreateSurveyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function createSurvey(
        string $title,
        ?string $description,
        ?string $instructions,
        ?string $opdPengampu,
        ?int $estimatedTime,
        ?string $thumbnailPath,
        string $status,
        int $createdBy,
        ?string $opensAt,
        ?string $closesAt
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO surveys (title, description, instructions, opd_pengampu, estimated_time, thumbnail_path, status, created_by, opens_at, closes_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $title,
            $description,
            $instructions,
            $opdPengampu,
            $estimatedTime,
            $thumbnailPath,
            $status,
            $createdBy,
            $opensAt,
            $closesAt
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createSurveyRestrictions(int $surveyId, array $positions): void
    {
        if ($positions === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, \count($positions), '(?, ?)'));
        $values = [];

        foreach ($positions as $position) {
            $values[] = $surveyId;
            $values[] = $position;
        }

        $stmt = $this->pdo->prepare("
        INSERT INTO survey_restrictions (survey_id, position)
        VALUES $placeholders
        ");

        $stmt->execute($values);
    }
}
