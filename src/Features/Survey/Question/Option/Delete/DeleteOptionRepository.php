<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Delete;

use PDO;
use App\Database;

final class DeleteOptionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function questionBelongsToSurvey(int $questionId, int $surveyId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM questions WHERE id = ? AND survey_id = ?");
        $stmt->execute([$questionId, $surveyId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function optionBelongsToQuestion(int $optionId, int $questionId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM options WHERE id = ? AND question_id = ?");
        $stmt->execute([$optionId, $questionId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function deleteOption(int $optionId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM options WHERE id = ?");
        $stmt->execute([$optionId]);
    }
}