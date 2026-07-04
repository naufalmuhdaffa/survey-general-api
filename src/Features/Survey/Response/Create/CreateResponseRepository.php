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

    public function surveyIsOpen(int $surveyId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM surveys
            WHERE id = ?
            AND status = 'open'
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
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM responses
            WHERE survey_id = ?
                AND user_id = ?
                AND status = 'submitted'
        ");
        $stmt->execute([$surveyId, $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getUserResponse(int $surveyId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, status, current_page, submitted_at
            FROM responses
            WHERE survey_id = ?
                AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$surveyId, $userId]);
        $response = $stmt->fetch();

        if (!$response) {
            return null;
        }

        $answers = $this->getAnswersByResponseId((int) $response['id']);

        return [
            'id' => (int) $response['id'],
            'status' => $response['status'],
            'current_page' => (int) ($response['current_page'] ?? 0),
            'submitted_at' => $response['submitted_at'],
            'answers' => $answers,
        ];
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

    public function saveDraftResponse(
        int $surveyId,
        int $userId,
        int $currentPage,
        array $answers
    ): int {
        return $this->persistResponse(
            $surveyId,
            $userId,
            'draft',
            $currentPage,
            null,
            $answers
        );
    }

    public function submitResponse(int $surveyId, int $userId, array $answers): int
    {
        return $this->persistResponse(
            $surveyId,
            $userId,
            'submitted',
            0,
            'NOW()',
            $answers
        );
    }

    public function createResponse(int $surveyId, int $userId, array $answers): int
    {
        return $this->submitResponse($surveyId, $userId, $answers);
    }

    private function persistResponse(
        int $surveyId,
        int $userId,
        string $status,
        int $currentPage,
        ?string $submittedAtExpression,
        array $answers
    ): int
    {
        $this->pdo->beginTransaction();

        try {
            $responseId = $this->findUserResponseId($surveyId, $userId);

            if ($responseId === null) {
                $submittedAtSql = $submittedAtExpression ?? 'NULL';
                $stmt = $this->pdo->prepare("
                    INSERT INTO responses (survey_id, user_id, status, current_page, submitted_at)
                    VALUES (?, ?, ?, ?, {$submittedAtSql})
                ");
                $stmt->execute([$surveyId, $userId, $status, $currentPage]);
                $responseId = (int) $this->pdo->lastInsertId();
            } else {
                $submittedAtSql = $submittedAtExpression ?? 'NULL';
                $stmt = $this->pdo->prepare("
                    UPDATE responses
                    SET status = ?,
                        current_page = ?,
                        submitted_at = {$submittedAtSql}
                    WHERE id = ?
                ");
                $stmt->execute([$status, $currentPage, $responseId]);

                $deleteStmt = $this->pdo->prepare("DELETE FROM answers WHERE response_id = ?");
                $deleteStmt->execute([$responseId]);
            }

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

    private function findUserResponseId(int $surveyId, int $userId): ?int
    {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM responses
            WHERE survey_id = ?
                AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$surveyId, $userId]);
        $responseId = $stmt->fetchColumn();

        return $responseId !== false ? (int) $responseId : null;
    }

    private function getAnswersByResponseId(int $responseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT question_id, answer_text, option_id, file_path
            FROM answers
            WHERE response_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$responseId]);

        return array_map(
            static fn (array $answer): array => [
                'question_id' => (int) $answer['question_id'],
                'answer_text' => $answer['answer_text'],
                'option_id' => $answer['option_id'] !== null ? (int) $answer['option_id'] : null,
                'file_path' => $answer['file_path'],
            ],
            $stmt->fetchAll()
        );
    }
}
