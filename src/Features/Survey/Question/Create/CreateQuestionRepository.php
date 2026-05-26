<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Create;

use PDO;
use App\Database;

final class CreateQuestionRepository
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

    public function getNextQuestionOrder(int $surveyId): int
    {
        $stmt = $this->pdo->prepare("SELECT MAX(question_order) FROM questions WHERE survey_id = ?");
        $stmt->execute([$surveyId]);
        return (int) $stmt->fetchColumn() + 1;
    }

    public function createQuestion(
        int $surveyId,
        string $questionText,
        string $questionType,
        bool $isRequired,
        int $questionOrder,
        int $page,
        ?int $parentOptionId
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO questions (survey_id, question_text, question_type, is_required, question_order, page, parent_option_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$surveyId, $questionText, $questionType, (int) $isRequired, $questionOrder, $page, $parentOptionId]);
        return (int) $this->pdo->lastInsertId();
    }
}
