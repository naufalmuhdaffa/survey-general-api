<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Create;

use PDO;
use App\Database;

final class CreateOptionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function questionExists(int $questionId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM questions WHERE id = ?");
        $stmt->execute([$questionId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function questionBelongsToSurvey(int $questionId, int $surveyId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM questions WHERE id = ? AND survey_id = ?");
        $stmt->execute([$questionId, $surveyId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function questionTypeAllowsOptions(int $questionId): bool
    {
        $stmt = $this->pdo->prepare("SELECT question_type FROM questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $type = $stmt->fetchColumn();
        return \in_array($type, ['radio_button', 'checkbox', 'dropdown', 'rating_scale']);
    }

    public function getNextOptionOrder(int $questionId): int
    {
        $stmt = $this->pdo->prepare("SELECT MAX(option_order) FROM options WHERE question_id = ?");
        $stmt->execute([$questionId]);
        return (int) $stmt->fetchColumn() + 1;
    }

    public function createOption(int $questionId, string $optionText, int $optionOrder): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO options (question_id, option_text, option_order) VALUES (?, ?, ?)");
        $stmt->execute([$questionId, $optionText, $optionOrder]);
        return (int) $this->pdo->lastInsertId();
    }
}