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
            SELECT id, title, description, opens_at, closes_at,
                CASE
                    WHEN opens_at IS NULL OR NOW() < opens_at THEN 'upcoming'
                    WHEN NOW() BETWEEN opens_at AND closes_at THEN 'active'
                    ELSE 'closed'
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
}