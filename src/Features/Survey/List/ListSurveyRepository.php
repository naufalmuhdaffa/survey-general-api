<?php

declare(strict_types=1);

namespace App\Features\Survey\List;

use PDO;
use App\Database;

final class ListSurveyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getAllSurveys(?string $position): array
    {
        $stmt = $this->pdo->prepare("
        SELECT s.id, s.title, s.description, 
        COALESCE(s.thumbnail_path, '/uploads/survey-thumbnails/default.svg') AS thumbnail_path, 
        s.status, s.opens_at, s.closes_at
        FROM surveys s
        WHERE (
            NOT EXISTS (
                SELECT 1
                FROM survey_restrictions sr
                WHERE sr.survey_id = s.id
            )
            OR EXISTS (
                SELECT 1
                FROM survey_restrictions sr
                WHERE sr.survey_id = s.id
                AND sr.position IN ('public', ?)
            )
        )
        AND s.status IN ('open', 'upcoming')
        ORDER BY s.created_at DESC
        ");

        $stmt->execute([$position]);

        return $stmt->fetchAll();
    }
}
