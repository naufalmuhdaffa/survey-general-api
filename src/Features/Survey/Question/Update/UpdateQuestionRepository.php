<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Update;

use PDO;
use App\Database;

final class UpdateQuestionRepository
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

    public function updateQuestion(int $questionId, array $fields): void
    {
        $setClauses = [];
        $values = [];

        foreach ($fields as $key => $value) {
            $setClauses[] = "$key = ?";
            $values[] = $value;
        }

        $values[] = $questionId;

        $sql = "UPDATE questions SET " . implode(', ', $setClauses) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
    }
}