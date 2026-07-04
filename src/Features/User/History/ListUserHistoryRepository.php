<?php

declare(strict_types=1);

namespace App\Features\User\History;

use App\Database;
use PDO;

final class ListUserHistoryRepository
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

    public function countHistory(
        int $userId,
        string $search,
        ?string $position,
        ?string $status,
    ): int {
        $params = [$userId];
        $where = $this->filterCondition($search, $position, $status, $params);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM responses r
            JOIN surveys s ON s.id = r.survey_id
            WHERE {$where}
        ");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getHistory(
        int $userId,
        string $search,
        ?string $position,
        ?string $status,
        string $sortBy,
        string $sortDirection,
        int $limit,
        int $offset,
    ): array {
        $params = [$userId];
        $where = $this->filterCondition($search, $position, $status, $params);
        $orderBy = $this->sortClause($sortBy, $sortDirection);
        $effectiveStatus = $this->effectiveStatusExpression();

        $stmt = $this->pdo->prepare("
            SELECT
                r.id AS response_id,
                r.status AS response_status,
                r.current_page,
                r.submitted_at,
                r.created_at AS response_created_at,
                r.updated_at AS response_updated_at,
                s.id AS survey_id,
                s.title,
                s.description,
                s.estimated_time,
                COALESCE(s.thumbnail_path, '/uploads/survey-thumbnails/default.svg') AS thumbnail_path,
                {$effectiveStatus} AS survey_status,
                s.opens_at,
                s.closes_at,
                COALESCE((
                    SELECT GROUP_CONCAT(
                        sr.position
                        ORDER BY FIELD(sr.position, 'asn', 'non_asn', 'public')
                        SEPARATOR ','
                    )
                    FROM survey_restrictions sr
                    WHERE sr.survey_id = s.id
                ), '') AS positions
            FROM responses r
            JOIN surveys s ON s.id = r.survey_id
            WHERE {$where}
            {$orderBy}
            LIMIT ? OFFSET ?
        ");

        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param);
        }

        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function sortClause(string $sortBy, string $sortDirection): string
    {
        $direction = $sortDirection === 'asc' ? 'ASC' : 'DESC';
        $column = match ($sortBy) {
            'positions' => 'positions',
            'status' => 'r.status',
            'title' => 's.title',
            default => 'COALESCE(r.submitted_at, r.updated_at, r.created_at)',
        };

        return "ORDER BY {$column} {$direction}, r.id {$direction}";
    }

    private function filterCondition(
        string $search,
        ?string $position,
        ?string $status,
        array &$params,
    ): string {
        $conditions = [
            'r.user_id = ?',
            "r.status IN ('draft', 'submitted')",
        ];

        if ($search !== '') {
            $keyword = '%' . $search . '%';
            $conditions[] = '(s.title LIKE ? OR s.description LIKE ?)';
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if ($status !== null) {
            $conditions[] = 'r.status = ?';
            $params[] = $status;
        }

        if ($position === 'public') {
            $conditions[] = "(
                NOT EXISTS (
                    SELECT 1
                    FROM survey_restrictions sr_position
                    WHERE sr_position.survey_id = s.id
                )
                OR EXISTS (
                    SELECT 1
                    FROM survey_restrictions sr_position
                    WHERE sr_position.survey_id = s.id
                    AND sr_position.position = 'public'
                )
            )";
        } elseif ($position !== null) {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM survey_restrictions sr_position
                WHERE sr_position.survey_id = s.id
                AND sr_position.position = ?
            )";
            $params[] = $position;
        }

        return implode(' AND ', $conditions);
    }
}
