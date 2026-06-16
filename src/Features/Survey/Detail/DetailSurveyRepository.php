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

    public function getPagesWithQuestionsBySurveyId(int $surveyId): array
    {
        $pages = $this->getPagesBySurveyId($surveyId);
        $questions = $this->getQuestionsBySurveyId($surveyId);
        $optionsByQuestionId = $this->getOptionsByQuestionIds(
            array_column($questions, 'id')
        );

        foreach ($questions as $question) {
            $page = (int) $question['page'];
            $questionId = (int) $question['id'];
            $question['is_required'] = (bool) $question['is_required'];
            $question['options'] = $optionsByQuestionId[$questionId] ?? [];

            if (!isset($pages[$page])) {
                $pages[$page] = [
                    'page' => $page,
                    'section' => null,
                    'questions' => [],
                ];
            }

            $pages[$page]['questions'][] = $question;
        }

        ksort($pages);

        return array_values($pages);
    }

    private function getPagesBySurveyId(int $surveyId): array
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

    private function getQuestionsBySurveyId(int $surveyId): array
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

    private function getOptionsByQuestionIds(array $questionIds): array
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

    public function canAccessSurvey(int $surveyId, ?string $position): bool
    {
        $stmt = $this->pdo->prepare("
        SELECT COUNT(*)
        FROM surveys s
        WHERE s.id = ?
        AND s.status IN ('open', 'upcoming')
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
