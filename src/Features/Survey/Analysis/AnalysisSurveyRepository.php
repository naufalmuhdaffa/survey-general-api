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

    public function getSummary(?int $createdBy, ?int $year = null): array
    {
        $effectiveStatus = $this->effectiveStatusExpression();
        $surveyParams = [];
        $surveyYearCondition = $this->surveyYearCondition($createdBy, $year, $surveyParams);

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS total_surveys,
                SUM(CASE WHEN ({$effectiveStatus}) = 'open' THEN 1 ELSE 0 END) AS active_surveys,
                SUM(CASE WHEN ({$effectiveStatus}) = 'closed' THEN 1 ELSE 0 END) AS closed_surveys
            FROM surveys s
            {$surveyYearCondition}
        ");
        $stmt->execute($surveyParams);

        $surveySummary = $stmt->fetch() ?: [];
        $responseParams = [];
        $responseWhere = $this->submittedResponseCondition($createdBy, $year, $responseParams);

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(DISTINCT r.user_id) AS total_respondents,
                COUNT(DISTINCT CASE WHEN u.position = 'asn' THEN r.user_id END) AS asn_respondents,
                COUNT(DISTINCT CASE WHEN u.position = 'non_asn' THEN r.user_id END) AS non_asn_respondents,
                COUNT(DISTINCT CASE WHEN u.position = 'public' THEN r.user_id END) AS public_respondents
            FROM responses r
            JOIN surveys s ON s.id = r.survey_id
            JOIN users u ON u.id = r.user_id
            {$responseWhere}
        ");
        $stmt->execute($responseParams);

        $responseSummary = $stmt->fetch() ?: [];

        return array_merge($surveySummary, $responseSummary);
    }

    public function getAvailableYears(?int $createdBy): array
    {
        $surveyCreatedWhere = 'WHERE created_at IS NOT NULL';
        $surveyOpenWhere = 'WHERE opens_at IS NOT NULL';
        $responseWhere = 'WHERE r.submitted_at IS NOT NULL';
        $params = [];

        if ($createdBy !== null) {
            $surveyCreatedWhere = 'WHERE created_by = ? AND created_at IS NOT NULL';
            $surveyOpenWhere = 'WHERE created_by = ? AND opens_at IS NOT NULL';
            $responseWhere = 'WHERE s.created_by = ? AND r.submitted_at IS NOT NULL';
            $params = [$createdBy, $createdBy, $createdBy];
        }

        $stmt = $this->pdo->prepare("
            SELECT DISTINCT year_value
            FROM (
                SELECT YEAR(created_at) AS year_value
                FROM surveys
                {$surveyCreatedWhere}
                UNION
                SELECT YEAR(opens_at) AS year_value
                FROM surveys
                {$surveyOpenWhere}
                UNION
                SELECT YEAR(r.submitted_at) AS year_value
                FROM responses r
                JOIN surveys s ON s.id = r.survey_id
                {$responseWhere}
            ) years
            WHERE year_value IS NOT NULL
            ORDER BY year_value DESC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getResponseVolume(?int $createdBy, int $year): array
    {
        $params = [
            $year . '-01-01 00:00:00',
            ($year + 1) . '-01-01 00:00:00',
        ];
        $ownerCondition = '';

        if ($createdBy !== null) {
            $ownerCondition = 'AND s.created_by = ?';
            $params[] = $createdBy;
        }

        $stmt = $this->pdo->prepare("
            SELECT MONTH(r.submitted_at) AS month, COUNT(*) AS total
            FROM responses r
            JOIN surveys s ON s.id = r.survey_id
            WHERE r.status = 'submitted'
                AND r.submitted_at IS NOT NULL
                AND r.submitted_at >= ?
                AND r.submitted_at < ?
                {$ownerCondition}
            GROUP BY MONTH(r.submitted_at)
            ORDER BY month ASC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function countSurveys(
        ?int $createdBy,
        string $search,
        ?string $status,
        ?string $audience,
        ?int $year
    ): int
    {
        $params = [];
        $where = $this->filterCondition($createdBy, $search, $status, $audience, $year, $params);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM surveys s
            {$where}
        ");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getSurveys(
        ?int $createdBy,
        string $search,
        ?string $status,
        ?string $audience,
        ?int $year,
        int $limit,
        int $offset
    ): array {
        $params = [];
        $where = $this->filterCondition($createdBy, $search, $status, $audience, $year, $params);
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

    public function getSurveyById(int $surveyId, ?int $createdBy): array|false
    {
        $effectiveStatus = $this->effectiveStatusExpression();
        $params = [$surveyId];
        $ownerCondition = '';

        if ($createdBy !== null) {
            $ownerCondition = 'AND s.created_by = ?';
            $params[] = $createdBy;
        }

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
            WHERE s.id = ? {$ownerCondition}
        ");
        $stmt->execute($params);

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
                u.nik,
                u.profile_photo_path
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
                u.nik,
                u.profile_photo_path
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

    private function filterCondition(
        ?int $createdBy,
        string $search,
        ?string $status,
        ?string $audience,
        ?int $year,
        array &$params
    ): string
    {
        $conditions = [];
        $effectiveStatus = $this->effectiveStatusExpression();

        if ($createdBy !== null) {
            $conditions[] = 's.created_by = ?';
            $params[] = $createdBy;
        }

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

        if ($audience === 'public') {
            $conditions[] = "(
                NOT EXISTS (
                    SELECT 1
                    FROM survey_restrictions sr_audience
                    WHERE sr_audience.survey_id = s.id
                )
                OR EXISTS (
                    SELECT 1
                    FROM survey_restrictions sr_audience
                    WHERE sr_audience.survey_id = s.id
                        AND sr_audience.position = 'public'
                )
            )";
        } elseif ($audience !== null) {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM survey_restrictions sr_audience
                WHERE sr_audience.survey_id = s.id
                    AND sr_audience.position = ?
            )";
            $params[] = $audience;
        }

        if ($year !== null) {
            $conditions[] = "(
                (s.created_at >= ? AND s.created_at < ?)
                OR (s.opens_at IS NOT NULL AND s.opens_at >= ? AND s.opens_at < ?)
                OR (s.closes_at IS NOT NULL AND s.closes_at >= ? AND s.closes_at < ?)
            )";

            $start = $year . '-01-01 00:00:00';
            $end = ($year + 1) . '-01-01 00:00:00';
            array_push($params, $start, $end, $start, $end, $start, $end);
        }

        return $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
    }

    private function surveyYearCondition(?int $createdBy, ?int $year, array &$params): string
    {
        $conditions = [];

        if ($createdBy !== null) {
            $conditions[] = 's.created_by = ?';
            $params[] = $createdBy;
        }

        if ($year !== null) {
            $start = $year . '-01-01 00:00:00';
            $end = ($year + 1) . '-01-01 00:00:00';
            $conditions[] = "(
                (s.created_at >= ? AND s.created_at < ?)
                OR (s.opens_at IS NOT NULL AND s.opens_at >= ? AND s.opens_at < ?)
                OR (s.closes_at IS NOT NULL AND s.closes_at >= ? AND s.closes_at < ?)
            )";
            array_push($params, $start, $end, $start, $end, $start, $end);
        }

        return $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
    }

    private function submittedResponseCondition(
        ?int $createdBy,
        ?int $year,
        array &$params
    ): string
    {
        $conditions = ["r.status = 'submitted'"];

        if ($createdBy !== null) {
            $conditions[] = 's.created_by = ?';
            $params[] = $createdBy;
        }

        if ($year !== null) {
            $conditions[] = 'r.submitted_at >= ? AND r.submitted_at < ?';
            $params[] = $year . '-01-01 00:00:00';
            $params[] = ($year + 1) . '-01-01 00:00:00';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
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
