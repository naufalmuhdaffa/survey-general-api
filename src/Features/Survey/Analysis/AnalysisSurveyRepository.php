<?php

declare(strict_types=1);

namespace App\Features\Survey\Analysis;

use App\Database;
use PDO;

final class AnalysisSurveyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    private function effectiveStatusExpression(string $alias = 's'): string
    {
        return "CASE
            WHEN {$alias}.status = 'draft' THEN 'draft'
            WHEN {$alias}.status = 'closed' THEN 'closed'
            WHEN {$alias}.closes_at IS NOT NULL AND {$alias}.closes_at <= NOW() THEN 'closed'
            WHEN {$alias}.opens_at IS NOT NULL AND {$alias}.opens_at > NOW() THEN 'upcoming'
            ELSE 'open'
        END";
    }

    public function getSummary(): array
    {
        $effectiveStatus = $this->effectiveStatusExpression();

        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS total_surveys,
                SUM(CASE WHEN ({$effectiveStatus}) = 'open' THEN 1 ELSE 0 END) AS active_surveys,
                SUM(CASE WHEN ({$effectiveStatus}) = 'closed' THEN 1 ELSE 0 END) AS closed_surveys,
                (
                    SELECT COUNT(*)
                    FROM responses r
                    WHERE r.status = 'submitted'
                ) AS total_respondents
            FROM surveys s
        ");

        return $stmt->fetch() ?: [];
    }

    public function getResponseVolume(string $startDate): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS period, COUNT(*) AS total
            FROM responses
            WHERE status = 'submitted'
                AND submitted_at IS NOT NULL
                AND submitted_at >= ?
            GROUP BY DATE_FORMAT(submitted_at, '%Y-%m')
            ORDER BY period ASC
        ");
        $stmt->execute([$startDate]);

        return $stmt->fetchAll();
    }

    public function countSurveys(string $search, ?string $status): int
    {
        $params = [];
        $where = $this->filterCondition($search, $status, $params);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM surveys s
            {$where}
        ");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getSurveys(
        string $search,
        ?string $status,
        int $limit,
        int $offset
    ): array {
        $params = [];
        $where = $this->filterCondition($search, $status, $params);
        $effectiveStatus = $this->effectiveStatusExpression();

        $stmt = $this->pdo->prepare("
            SELECT
                s.id,
                s.title,
                s.description,
                s.opd_pengampu,
                s.opens_at,
                s.closes_at,
                {$effectiveStatus} AS status,
                (
                    SELECT COUNT(*)
                    FROM responses r
                    WHERE r.survey_id = s.id
                        AND r.status = 'submitted'
                ) AS response_count,
                COALESCE((
                    SELECT GROUP_CONCAT(
                        sr.position
                        ORDER BY FIELD(sr.position, 'asn', 'non_asn', 'public')
                        SEPARATOR ','
                    )
                    FROM survey_restrictions sr
                    WHERE sr.survey_id = s.id
                ), '') AS positions
            FROM surveys s
            {$where}
            ORDER BY s.updated_at DESC, s.created_at DESC, s.id DESC
            LIMIT ? OFFSET ?
        ");

        $index = 1;
        foreach ($params as $param) {
            $stmt->bindValue($index, $param, PDO::PARAM_STR);
            $index++;
        }

        $stmt->bindValue($index, $limit, PDO::PARAM_INT);
        $stmt->bindValue($index + 1, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getSurveyById(int $surveyId): array|false
    {
        $effectiveStatus = $this->effectiveStatusExpression();

        $stmt = $this->pdo->prepare("
            SELECT
                s.id,
                s.title,
                s.description,
                s.opd_pengampu,
                s.opens_at,
                s.closes_at,
                s.estimated_time,
                {$effectiveStatus} AS status,
                COALESCE((
                    SELECT GROUP_CONCAT(
                        sr.position
                        ORDER BY FIELD(sr.position, 'asn', 'non_asn', 'public')
                        SEPARATOR ','
                    )
                    FROM survey_restrictions sr
                    WHERE sr.survey_id = s.id
                ), '') AS positions
            FROM surveys s
            WHERE s.id = ?
        ");
        $stmt->execute([$surveyId]);

        return $stmt->fetch();
    }

    public function countSubmittedResponses(int $surveyId, string $search = ''): int
    {
        $params = [$surveyId];
        $searchCondition = $this->respondentSearchCondition($search, $params);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM responses r
            JOIN users u ON u.id = r.user_id
            WHERE r.survey_id = ?
                AND r.status = 'submitted'
                {$searchCondition}
        ");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getRespondents(
        int $surveyId,
        string $search,
        int $limit,
        int $offset
    ): array {
        $params = [$surveyId];
        $searchCondition = $this->respondentSearchCondition($search, $params);

        $stmt = $this->pdo->prepare("
            SELECT
                r.id AS response_id,
                r.status,
                r.submitted_at,
                u.id AS user_id,
                u.full_name,
                u.nik
            FROM responses r
            JOIN users u ON u.id = r.user_id
            WHERE r.survey_id = ?
                AND r.status = 'submitted'
                {$searchCondition}
            ORDER BY r.submitted_at DESC, r.id DESC
            LIMIT ? OFFSET ?
        ");

        $index = 1;
        foreach ($params as $param) {
            $stmt->bindValue($index, $param, PDO::PARAM_STR);
            $index++;
        }

        $stmt->bindValue($index, $limit, PDO::PARAM_INT);
        $stmt->bindValue($index + 1, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getAllSubmittedResponses(int $surveyId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                r.id AS response_id,
                r.submitted_at,
                u.full_name,
                u.nik
            FROM responses r
            JOIN users u ON u.id = r.user_id
            WHERE r.survey_id = ?
                AND r.status = 'submitted'
            ORDER BY r.submitted_at DESC, r.id DESC
        ");
        $stmt->execute([$surveyId]);

        return $stmt->fetchAll();
    }

    public function getPagesWithQuestions(int $surveyId): array
    {
        $pages = $this->getPagesBySurveyId($surveyId);
        $questions = $this->getQuestionsBySurveyId($surveyId);
        $options = $this->getOptionsByQuestionIds(array_column($questions, 'id'));

        foreach ($questions as $question) {
            $page = (int) $question['page'];
            $questionId = (int) $question['id'];
            $question['is_required'] = (bool) $question['is_required'];
            $question['options'] = $options[$questionId] ?? [];

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

    public function getOptionAnswerCounts(int $surveyId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                q.id AS question_id,
                o.id AS option_id,
                COUNT(r.id) AS response_count
            FROM questions q
            JOIN options o ON o.question_id = q.id
            LEFT JOIN answers a ON a.option_id = o.id
            LEFT JOIN responses r ON r.id = a.response_id
                AND r.status = 'submitted'
            WHERE q.survey_id = ?
            GROUP BY q.id, o.id
        ");
        $stmt->execute([$surveyId]);

        return $stmt->fetchAll();
    }

    public function getFreeTextAnswers(int $surveyId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                q.id AS question_id,
                a.answer_text,
                r.submitted_at,
                u.full_name
            FROM answers a
            JOIN questions q ON q.id = a.question_id
            JOIN responses r ON r.id = a.response_id
            JOIN users u ON u.id = r.user_id
            WHERE q.survey_id = ?
                AND q.question_type = 'free_text'
                AND r.status = 'submitted'
                AND a.answer_text IS NOT NULL
                AND TRIM(a.answer_text) <> ''
            ORDER BY r.submitted_at DESC, a.id DESC
        ");
        $stmt->execute([$surveyId]);

        return $stmt->fetchAll();
    }

    public function getAnswersByResponseIds(array $responseIds): array
    {
        $responseIds = array_values(array_unique(array_map('intval', $responseIds)));

        if ($responseIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($responseIds), '?'));

        $stmt = $this->pdo->prepare("
            SELECT
                a.response_id,
                q.id AS question_id,
                q.question_text,
                q.question_type,
                a.answer_text,
                o.option_text
            FROM answers a
            JOIN questions q ON q.id = a.question_id
            LEFT JOIN options o ON o.id = a.option_id
            WHERE a.response_id IN ({$placeholders})
            ORDER BY q.page ASC, q.question_order ASC, a.id ASC
        ");
        $stmt->execute($responseIds);

        return $stmt->fetchAll();
    }

    private function filterCondition(string $search, ?string $status, array &$params): string
    {
        $conditions = [];
        $effectiveStatus = $this->effectiveStatusExpression();

        if ($search !== '') {
            $keyword = '%' . $search . '%';
            $conditions[] = '(s.title LIKE ? OR s.description LIKE ? OR s.opd_pengampu LIKE ?)';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if ($status !== null) {
            $conditions[] = "({$effectiveStatus}) = ?";
            $params[] = $status;
        }

        return $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
    }

    private function respondentSearchCondition(string $search, array &$params): string
    {
        if ($search === '') {
            return '';
        }

        $keyword = '%' . $search . '%';
        $params[] = $keyword;
        $params[] = $keyword;

        return 'AND (u.full_name LIKE ? OR u.nik LIKE ?)';
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

        if ($questionIds === []) {
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
