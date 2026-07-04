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
        string $sort,
        int $limit,
        int $offset,
    ): array {
        $params = [$userId];
        $where = $this->filterCondition($search, $position, $status, $params);
        $orderDirection = $sort === 'oldest' ? 'ASC' : 'DESC';

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
                s.status AS survey_status,
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
            ORDER BY COALESCE(r.submitted_at, r.updated_at, r.created_at) {$orderDirection}, r.id {$orderDirection}
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
