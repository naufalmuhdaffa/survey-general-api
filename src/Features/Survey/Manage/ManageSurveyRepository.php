<?php

declare(strict_types=1);

namespace App\Features\Survey\Manage;

use App\Database;
use PDO;

final class ManageSurveyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function countSurveys(string $search, ?string $status, ?string $position): int
    {
        $params = [];
        $where = $this->filterCondition($search, $status, $position, $params);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM surveys s
            JOIN users u ON u.id = s.created_by
            $where
        ");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSurveys(
        string $search,
        ?string $status,
        ?string $position,
        ?string $sortBy,
        string $sortDirection,
        int $limit,
        int $offset
    ): array
    {
        $params = [];
        $where = $this->filterCondition($search, $status, $position, $params);
        $orderBy = $this->sortClause($sortBy, $sortDirection);

        $stmt = $this->pdo->prepare("
            SELECT
                s.id,
                s.title,
                s.description,
                s.instructions,
                s.estimated_time,
                COALESCE(s.thumbnail_path, '/uploads/survey-thumbnails/default.svg') AS thumbnail_path,
                s.status,
                s.opens_at,
                s.closes_at,
                s.created_at,
                s.updated_at,
                u.full_name AS creator_name,
                (
                    SELECT COUNT(*)
                    FROM questions q
                    WHERE q.survey_id = s.id
                ) AS question_count,
                (
                    SELECT COUNT(*)
                    FROM responses r
                    WHERE r.survey_id = s.id
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
            JOIN users u ON u.id = s.created_by
            $where
            $orderBy
            LIMIT ? OFFSET ?
        ");

        $parameterIndex = 1;

        foreach ($params as $param) {
            $stmt->bindValue($parameterIndex, $param, PDO::PARAM_STR);
            $parameterIndex++;
        }

        $stmt->bindValue($parameterIndex, $limit, PDO::PARAM_INT);
        $stmt->bindValue($parameterIndex + 1, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function sortClause(?string $sortBy, string $sortDirection): string
    {
        if ($sortBy === null) {
            return 'ORDER BY s.updated_at DESC, s.created_at DESC, s.id DESC';
        }

        $direction = $sortDirection === 'desc' ? 'DESC' : 'ASC';
        $column = match ($sortBy) {
            'opens_at' => 's.opens_at',
            'positions' => 'positions',
            'status' => 's.status',
            'title' => 's.title',
            default => 's.updated_at',
        };

        return "ORDER BY $column $direction, s.id DESC";
    }

    /**
     * @param list<string> $params
     */
    private function filterCondition(
        string $search,
        ?string $status,
        ?string $position,
        array &$params
    ): string
    {
        $conditions = [];

        if ($search !== '') {
            $keyword = '%' . $search . '%';
            $params = [$keyword, $keyword, $keyword, $keyword];

            $conditions[] = "(
                s.title LIKE ?
                OR s.description LIKE ?
                OR s.status LIKE ?
                OR u.full_name LIKE ?
            )";
        }

        if ($status !== null) {
            $conditions[] = 's.status = ?';
            $params[] = $status;
        }

        if ($position !== null) {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM survey_restrictions sr_filter
                WHERE sr_filter.survey_id = s.id
                AND sr_filter.position = ?
            )";
            $params[] = $position;
        }

        if ($conditions === []) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }
}
