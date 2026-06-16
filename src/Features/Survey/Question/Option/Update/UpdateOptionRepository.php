<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Update;

use PDO;
use App\Database;

final class UpdateOptionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function optionExists(int $optionId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM options WHERE id = ?");
        $stmt->execute([$optionId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function questionBelongsToSurvey(int $questionId, int $surveyId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM questions WHERE id = ? AND survey_id = ?");
        $stmt->execute([$questionId, $surveyId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function surveyIsDraft(int $surveyId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM surveys WHERE id = ? AND status = 'draft'");
        $stmt->execute([$surveyId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function optionBelongsToQuestion(int $optionId, int $questionId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM options WHERE id = ? AND question_id = ?");
        $stmt->execute([$optionId, $questionId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function updateOption(int $optionId, array $fields): void
    {
        $setClauses = [];
        $values = [];

        foreach ($fields as $key => $value) {
            $setClauses[] = "$key = ?";
            $values[] = $value;
        }

        $values[] = $optionId;

        $sql = "UPDATE options SET " . implode(', ', $setClauses) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
    }
}
