<?php

declare(strict_types=1);

namespace App\Features\Survey\Detail;

use PDO;
use App\Database;

final class DetailSurveyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getSurveyById(int $id): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, title, description, instructions, estimated_time,
            COALESCE(thumbnail_path, '/uploads/survey-thumbnails/default.svg') AS thumbnail_path, 
            status, opens_at, closes_at
            FROM surveys
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getRestrictionsBySurveyId(int $surveyId): array
    {
        $stmt = $this->pdo->prepare("
        SELECT position
        FROM survey_restrictions
        WHERE survey_id = ?
        ");
        $stmt->execute([$surveyId]);
        return array_column($stmt->fetchAll(), 'position');
    }

    public function canAccessSurvey(int $surveyId, ?string $position): bool
    {
        $stmt = $this->pdo->prepare("
        SELECT COUNT(*)
        FROM surveys s
        WHERE s.id = ?
        AND (
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
    ");

        $stmt->execute([$surveyId, $position]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
