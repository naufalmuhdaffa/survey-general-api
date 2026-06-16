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

    public function getSurveyStatus(int $surveyId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT status FROM surveys WHERE id = ?");
        $stmt->execute([$surveyId]);
        $status = $stmt->fetchColumn();

        return \is_string($status) ? $status : null;
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

    public function deleteRestrictions(int $surveyId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM survey_restrictions WHERE survey_id = ?");
        $stmt->execute([$surveyId]);
    }

    public function createSurveyRestrictions(int $surveyId, array $positions): void
    {
        if ($positions === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, \count($positions), '(?, ?)'));
        $values = [];

        foreach ($positions as $position) {
            $values[] = $surveyId;
            $values[] = $position;
        }

        $stmt = $this->pdo->prepare("
        INSERT INTO survey_restrictions (survey_id, position)
        VALUES $placeholders
        ");

        $stmt->execute($values);
    }
}
