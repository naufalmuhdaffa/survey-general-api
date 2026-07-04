<?php

declare(strict_types=1);

namespace App\Features\Survey\List;

use PDO;
use App\Database;

final class ListSurveyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function countSurveys(
        string $search,
        ?string $status,
        ?string $position,
        ?string $accountPosition,
    ): int
    {
        $params = [];
        $where = $this->filterCondition(
            $search,
            $status,
            $position,
            $accountPosition,
            $params,
        );
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM surveys s
            WHERE {$where}
        ");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getAllSurveys(
        string $search,
        ?string $status,
        ?string $position,
        ?string $accountPosition,
        ?int $userId,
        int $limit,
        int $offset,
    ): array {
        $params = [];
        $where = $this->filterCondition(
            $search,
            $status,
            $position,
            $accountPosition,
            $params,
        );
        $responseSelect = $userId !== null
            ? ",
                ur.status AS user_response_status,
                ur.submitted_at AS user_response_submitted_at,
                ur.current_page AS user_response_current_page"
            : ",
                NULL AS user_response_status,
                NULL AS user_response_submitted_at,
                NULL AS user_response_current_page";
        $responseJoin = $userId !== null
            ? "LEFT JOIN responses ur ON ur.survey_id = s.id AND ur.user_id = ?"
            : "";

        $stmt = $this->pdo->prepare("
            SELECT
                s.id,
                s.title,
                s.description,
                s.estimated_time,
                COALESCE(s.thumbnail_path, '/uploads/survey-thumbnails/default.svg') AS thumbnail_path,
                s.status,
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
                {$responseSelect}
            FROM surveys s
            {$responseJoin}
            WHERE {$where}
            ORDER BY s.created_at DESC
            LIMIT ? OFFSET ?
        ");

        if ($userId !== null) {
            array_unshift($params, $userId);
        }

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
        ?string $status,
        ?string $position,
        ?string $accountPosition,
        array &$params,
    ): string {
        $accessPositions = ["'public'"];

        if ($accountPosition !== null) {
            $accessPositions[] = '?';
            $params[] = $accountPosition;
        }

        $conditions = [
            "(
                NOT EXISTS (
                    SELECT 1
                    FROM survey_restrictions sr_access
                    WHERE sr_access.survey_id = s.id
                )
                OR EXISTS (
                    SELECT 1
                    FROM survey_restrictions sr_access
                    WHERE sr_access.survey_id = s.id
                    AND sr_access.position IN (" . implode(', ', $accessPositions) . ")
                )
            )",
            "s.status IN ('open', 'upcoming')",
        ];

        if ($search !== '') {
            $keyword = '%' . $search . '%';
            $conditions[] = '(s.title LIKE ? OR s.description LIKE ?)';
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if ($status !== null) {
            $conditions[] = 's.status = ?';
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
            )
            ";
            $params[] = $position;
        }

        return implode(' AND ', $conditions);
    }
}
