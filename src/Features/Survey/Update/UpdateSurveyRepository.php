<?php

declare(strict_types=1);

namespace App\Features\Survey\Update;

use PDO;
use App\Database;

final class UpdateSurveyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function surveyExists(int $surveyId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM surveys WHERE id = ?");
        $stmt->execute([$surveyId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function updateSurvey(int $surveyId, array $fields): void
    {
        $setClauses = [];
        $values = [];

        foreach ($fields as $key => $value) {
            $setClauses[] = "$key = ?";
            $values[] = $value;
        }

        $values[] = $surveyId;

        $sql = "UPDATE surveys SET " . implode(', ', $setClauses) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
    }
}