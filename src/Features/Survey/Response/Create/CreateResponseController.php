<?php

declare(strict_types=1);

namespace App\Features\Survey\Response\Create;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use Throwable;
use function is_array;

final class CreateResponseController
{
    private CreateResponseRepository $repository;

    public function __construct()
    {
        $this->repository = new CreateResponseRepository();
    }

    public function create(int $surveyId): void
    {
        $user = AuthMiddleware::handle('user');
        $userId = (int) $user['id'];
        $position = (string) $user['position'];

        if (!$this->repository->surveyExists($surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }

        if (!$this->repository->surveyIsActive($surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak aktif'
            ], 422);
        }

        if (!$this->repository->userCanAccessSurvey($surveyId, $position)) {
            Response::json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki hak akses untuk survei ini'
            ], 403);
        }

        if ($this->repository->userHasSubmitted($surveyId, $userId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Anda sudah mengisi survei ini'
            ], 409);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $answers = $data['answers'] ?? null;

        if (!is_array($answers)) {
            Response::json([
                'status' => 'error',
                'message' => 'Field jawaban (answers) harus berupa array'
            ], 422);
        }

        $questions = $this->repository->getQuestionsBySurveyId($surveyId);
        $optionIdsByQuestionId = $this->repository->getOptionIdsByQuestionIds(array_keys($questions));
        $normalizedAnswers = $this->normalizeAnswers($answers, $questions, $optionIdsByQuestionId);

        try {
            $responseId = $this->repository->createResponse($surveyId, $userId, $normalizedAnswers);
        } catch (Throwable $e) {
            Response::json([
                'status' => 'error',
                'message' => 'Gagal menyimpan response'
            ], 500);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Response survei berhasil disimpan',
            'data' => ['id' => $responseId]
        ], 201);
    }

    private function normalizeAnswers(array $answers, array $questions, array $optionIdsByQuestionId): array
    {
        $normalizedAnswers = [];
        $answeredQuestionIds = [];

        foreach ($answers as $answer) {
            if (!is_array($answer)) {
                Response::json([
                    'status' => 'error',
                    'message' => 'Format answer tidak valid'
                ], 422);
            }

            $questionId = isset($answer['question_id']) ? (int) $answer['question_id'] : 0;

            if ($questionId <= 0 || !isset($questions[$questionId])) {
                Response::json([
                    'status' => 'error',
                    'message' => 'Question tidak ditemukan di survei ini'
                ], 422);
            }

            if (isset($answeredQuestionIds[$questionId])) {
                Response::json([
                    'status' => 'error',
                    'message' => 'Question tidak boleh diisi lebih dari sekali'
                ], 422);
            }

            $answeredQuestionIds[$questionId] = true;
            $questionType = $questions[$questionId]['question_type'];

            match ($questionType) {
                'free_text' => $this->normalizeFreeTextAnswer($answer, $questionId, $normalizedAnswers),
                'radio_button', 'dropdown', 'rating_scale' => $this->normalizeSingleOptionAnswer($answer, $questionId, $optionIdsByQuestionId, $normalizedAnswers),
                'checkbox' => $this->normalizeMultipleOptionAnswer($answer, $questionId, $optionIdsByQuestionId, $normalizedAnswers),
                'file_upload' => Response::json([
                    'status' => 'error',
                    'message' => 'File upload answer belum didukung'
                ], 422),
                default => Response::json([
                    'status' => 'error',
                    'message' => 'Tipe pertanyaan tidak valid'
                ], 422),
            };
        }

        $this->validateRequiredQuestions($questions, $answeredQuestionIds, $normalizedAnswers);

        return $normalizedAnswers;
    }

    private function validateRequiredQuestions(array $questions, array $answeredQuestionIds, array $normalizedAnswers): void
    {
        $selectedOptionIds = [];

        foreach ($normalizedAnswers as $answer) {
            if (isset($answer['option_id'])) {
                $selectedOptionIds[(int) $answer['option_id']] = true;
            }
        }

        foreach ($questions as $questionId => $question) {
            if (!(bool) $question['is_required']) {
                continue;
            }

            $parentOptionId = $question['parent_option_id'] !== null
                ? (int) $question['parent_option_id']
                : null;

            if ($parentOptionId !== null && !isset($selectedOptionIds[$parentOptionId])) {
                continue;
            }

            if (!isset($answeredQuestionIds[$questionId])) {
                Response::json([
                    'status' => 'error',
                    'message' => 'Pertanyaan wajib belum dijawab: ' . $questionId
                ], 422);
            }
        }
    }

    private function normalizeFreeTextAnswer(array $answer, int $questionId, array &$normalizedAnswers): void
    {
        $answerText = isset($answer['answer_text']) ? trim((string) $answer['answer_text']) : '';

        if ($answerText === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Answer text tidak boleh kosong'
            ], 422);
        }

        $normalizedAnswers[] = [
            'question_id' => $questionId,
            'answer_text' => $answerText,
        ];
    }

    private function normalizeSingleOptionAnswer(array $answer, int $questionId, array $optionIdsByQuestionId, array &$normalizedAnswers): void
    {
        $optionId = isset($answer['option_id']) ? (int) $answer['option_id'] : 0;

        if ($optionId <= 0 || !isset($optionIdsByQuestionId[$questionId][$optionId])) {
            Response::json([
                'status' => 'error',
                'message' => 'Option tidak valid untuk question ini'
            ], 422);
        }

        $normalizedAnswers[] = [
            'question_id' => $questionId,
            'option_id' => $optionId,
        ];
    }

    private function normalizeMultipleOptionAnswer(array $answer, int $questionId, array $optionIdsByQuestionId, array &$normalizedAnswers): void
    {
        $optionIds = $answer['option_ids'] ?? null;

        if (!is_array($optionIds) || empty($optionIds)) {
            Response::json([
                'status' => 'error',
                'message' => 'ID Opsi (option_ids) tidak boleh kosong'
            ], 422);
        }

        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));

        foreach ($optionIds as $optionId) {
            if ($optionId <= 0 || !isset($optionIdsByQuestionId[$questionId][$optionId])) {
                Response::json([
                    'status' => 'error',
                    'message' => 'Option tidak valid untuk question ini'
                ], 422);
            }

            $normalizedAnswers[] = [
                'question_id' => $questionId,
                'option_id' => $optionId,
            ];
        }
    }
}
