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

    public function getAllSurveys(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, title, description, opens_at, closes_at,
                CASE
                    WHEN opens_at IS NULL OR NOW() < opens_at THEN 'upcoming'
                    WHEN NOW() BETWEEN opens_at AND closes_at THEN 'active'
                    ELSE 'closed'
                END AS status
            FROM surveys
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
    }
}