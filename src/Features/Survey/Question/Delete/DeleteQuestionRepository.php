<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Delete;

use PDO;
use App\Database;

final class DeleteQuestionRepository
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

    public function surveyIsDraft(int $surveyId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM surveys WHERE id = ? AND status = 'draft'");
        $stmt->execute([$surveyId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function deleteQuestion(int $questionId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$questionId]);
    }
}
