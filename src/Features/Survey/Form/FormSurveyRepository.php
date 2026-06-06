<?php

declare(strict_types=1);

namespace App\Features\Survey\Form;

use App\Database;
use PDO;

final class FormSurveyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function canAccessSurvey(int $surveyId, ?string $position): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM surveys s
            WHERE s.id = ?
            AND s.status = 'open'
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

    public function getPagesBySurveyId(int $surveyId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT page, section
            FROM survey_pages
            WHERE survey_id = ?
            ORDER BY page ASC
        ");
        $stmt->execute([$surveyId]);

        $pages = [];

        foreach ($stmt->fetchAll() as $page) {
            $pageNumber = (int) $page['page'];

            $pages[$pageNumber] = [
                'page' => $pageNumber,
                'section' => $page['section'],
                'questions' => [],
            ];
        }

        return $pages;
    }

    public function getQuestionsBySurveyId(int $surveyId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, question_text, question_type, is_required, question_order, page, parent_option_id
            FROM questions
            WHERE survey_id = ?
            ORDER BY page ASC, question_order ASC
        ");
        $stmt->execute([$surveyId]);

        return $stmt->fetchAll();
    }

    public function getOptionsByQuestionIds(array $questionIds): array
    {
        $questionIds = array_values(array_unique(array_map('intval', $questionIds)));

        if (empty($questionIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($questionIds), '?'));

        $stmt = $this->pdo->prepare("
            SELECT question_id, id, option_text, option_order
            FROM options
            WHERE question_id IN ({$placeholders})
            ORDER BY question_id ASC, option_order ASC
        ");
        $stmt->execute($questionIds);

        $optionsByQuestionId = [];

        foreach ($stmt->fetchAll() as $option) {
            $questionId = (int) $option['question_id'];
            unset($option['question_id']);

            $optionsByQuestionId[$questionId][] = $option;
        }

        return $optionsByQuestionId;
    }
}
