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
            SELECT id, title, description, 
            COALESCE(thumbnail_path, '/uploads/survey-thumbnails/default.svg') AS thumbnail_path, 
            opens_at, closes_at,
                CASE
                    WHEN opens_at IS NOT NULL AND NOW() < opens_at THEN 'upcoming'
                    WHEN closes_at IS NOT NULL AND NOW() > closes_at THEN 'closed'
                    ELSE 'active'
                END AS status
            FROM surveys
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getQuestionsBySurveyId(int $surveyId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, question_text, question_type, question_order, page, parent_option_id
            FROM questions
            WHERE survey_id = ?
            ORDER BY page ASC, question_order ASC
        ");
        $stmt->execute([$surveyId]);
        return $stmt->fetchAll();
    }

    public function getOptionsByQuestionId(int $questionId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, option_text, option_order
            FROM options
            WHERE question_id = ?
            ORDER BY option_order ASC
        ");
        $stmt->execute([$questionId]);
        return $stmt->fetchAll();
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