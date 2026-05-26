<?php

declare(strict_types=1);

namespace App\Features\Survey\Response\Create;

use App\Database;
use PDO;
use Throwable;

final class CreateResponseRepository
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

    public function surveyIsActive(int $surveyId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM surveys
            WHERE id = ?
            AND (opens_at IS NULL OR NOW() >= opens_at)
            AND (closes_at IS NULL OR NOW() <= closes_at)
        ");
        $stmt->execute([$surveyId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function userCanAccessSurvey(int $surveyId, string $position): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM surveys s
            WHERE s.id = ?
            AND (
                NOT EXISTS (
                    SELECT 1
                    FROM survey_restrictions sr
                    WHERE sr.survey_id = s.id
                )
                OR EXISTS (
                    SELECT 1
                    FROM survey_restrictions sr
                    WHERE sr.survey_id = s.id
                    AND sr.position IN ('public', ?)
                )
            )
        ");
        $stmt->execute([$surveyId, $position]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function userHasSubmitted(int $surveyId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM responses WHERE survey_id = ? AND user_id = ?");
        $stmt->execute([$surveyId, $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getQuestionsBySurveyId(int $surveyId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, question_type, is_required, parent_option_id
            FROM questions
            WHERE survey_id = ?
        ");
        $stmt->execute([$surveyId]);

        $questions = [];

        foreach ($stmt->fetchAll() as $question) {
            $questions[(int) $question['id']] = $question;
        }

        return $questions;
    }

    public function getOptionIdsByQuestionIds(array $questionIds): array
    {
        $questionIds = array_values(array_unique(array_map('intval', $questionIds)));

        if (empty($questionIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($questionIds), '?'));

        $stmt = $this->pdo->prepare("
            SELECT id, question_id
            FROM options
            WHERE question_id IN ($placeholders)
        ");
        $stmt->execute($questionIds);

        $optionIdsByQuestionId = [];

        foreach ($stmt->fetchAll() as $option) {
            $questionId = (int) $option['question_id'];
            $optionId = (int) $option['id'];

            $optionIdsByQuestionId[$questionId][$optionId] = true;
        }

        return $optionIdsByQuestionId;
    }

    public function createResponse(int $surveyId, int $userId, array $answers): int
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("INSERT INTO responses (survey_id, user_id) VALUES (?, ?)");
            $stmt->execute([$surveyId, $userId]);
            $responseId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("
                INSERT INTO answers (response_id, question_id, answer_text, option_id, file_path)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($answers as $answer) {
                $stmt->execute([
                    $responseId,
                    $answer['question_id'],
                    $answer['answer_text'] ?? null,
                    $answer['option_id'] ?? null,
                    $answer['file_path'] ?? null,
                ]);
            }

            $this->pdo->commit();
            return $responseId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
