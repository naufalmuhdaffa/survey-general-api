<?php

declare(strict_types=1);

namespace App\Features\Survey\Response\Create;

use RuntimeException;
use Throwable;

final class CreateResponseService
{
    private CreateResponseRepository $repository;

    public function __construct()
    {
        $this->repository = new CreateResponseRepository();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function detail(int $surveyId, int $userId, string $position): array
    {
        $this->ensureSurveyCanBeFilled($surveyId, $position);

        return $this->repository->getUserResponse($surveyId, $userId) ?? [
            'status' => null,
            'current_page' => 0,
            'submitted_at' => null,
            'answers' => [],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function saveDraft(int $surveyId, int $userId, string $position, array $data): array
    {
        $this->ensureSurveyCanBeFilled($surveyId, $position);

        if ($this->repository->userHasSubmitted($surveyId, $userId)) {
            throw new RuntimeException('Anda sudah mengirim survei ini', 409);
        }

        $answers = $data['answers'] ?? [];

        if (!\is_array($answers)) {
            throw new RuntimeException('Field jawaban (answers) harus berupa array', 422);
        }

        $currentPage = $this->normalizeCurrentPage($data['page'] ?? 0);
        $questions = $this->repository->getQuestionsBySurveyId($surveyId);
        $optionIdsByQuestionId = $this->repository->getOptionIdsByQuestionIds(array_keys($questions));
        $normalizedAnswers = $this->normalizeAnswers($answers, $questions, $optionIdsByQuestionId, false);

        try {
            $responseId = $this->repository->saveDraftResponse(
                $surveyId,
                $userId,
                $currentPage,
                $normalizedAnswers
            );
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal menyimpan draft response', 500, $e);
        }

        return [
            'id' => $responseId,
            'status' => 'draft',
            'current_page' => $currentPage,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $surveyId, int $userId, string $position, array $data): int
    {
        return $this->submit($surveyId, $userId, $position, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function submit(int $surveyId, int $userId, string $position, array $data): int
    {
        $this->ensureSurveyCanBeFilled($surveyId, $position);

        if ($this->repository->userHasSubmitted($surveyId, $userId)) {
            throw new RuntimeException('Anda sudah mengisi survei ini', 409);
        }

        $answers = $data['answers'] ?? null;

        if (!\is_array($answers)) {
            throw new RuntimeException('Field jawaban (answers) harus berupa array', 422);
        }

        $questions = $this->repository->getQuestionsBySurveyId($surveyId);
        $optionIdsByQuestionId = $this->repository->getOptionIdsByQuestionIds(array_keys($questions));
        $normalizedAnswers = $this->normalizeAnswers($answers, $questions, $optionIdsByQuestionId, true);

        try {
            return $this->repository->submitResponse($surveyId, $userId, $normalizedAnswers);
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal menyimpan response', 500, $e);
        }
    }

    private function ensureSurveyCanBeFilled(int $surveyId, string $position): void
    {
        if (!$this->repository->surveyExists($surveyId)) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        if (!$this->repository->surveyIsOpen($surveyId)) {
            throw new RuntimeException('Survei belum dibuka atau sudah ditutup', 422);
        }

        if (!$this->repository->userCanAccessSurvey($surveyId, $position)) {
            throw new RuntimeException('Anda tidak memiliki hak akses untuk survei ini', 403);
        }
    }

    /**
     * @param array<int, mixed> $answers
     * @param array<int, array<string, mixed>> $questions
     * @param array<int, array<int, bool>> $optionIdsByQuestionId
     * @return list<array<string, int|string>>
     */
    private function normalizeAnswers(
        array $answers,
        array $questions,
        array $optionIdsByQuestionId,
        bool $validateRequired,
    ): array
    {
        $normalizedAnswers = [];
        $answeredQuestionIds = [];

        foreach ($answers as $answer) {
            if (!\is_array($answer)) {
                throw new RuntimeException('Format answer tidak valid', 422);
            }

            $questionId = $this->normalizePositiveInteger(
                $answer['question_id'] ?? null,
                'Question tidak ditemukan di survei ini'
            );

            if (!isset($questions[$questionId])) {
                throw new RuntimeException('Question tidak ditemukan di survei ini', 422);
            }

            if (isset($answeredQuestionIds[$questionId])) {
                throw new RuntimeException('Question tidak boleh diisi lebih dari sekali', 422);
            }

            $answeredQuestionIds[$questionId] = true;
            $questionType = (string) $questions[$questionId]['question_type'];

            $answersForQuestion = match ($questionType) {
                'free_text' => $this->normalizeFreeTextAnswer($answer, $questionId),
                'radio_button', 'dropdown', 'rating_scale' => $this->normalizeSingleOptionAnswer(
                    $answer,
                    $questionId,
                    $optionIdsByQuestionId
                ),
                'checkbox' => $this->normalizeMultipleOptionAnswer(
                    $answer,
                    $questionId,
                    $optionIdsByQuestionId
                ),
                'file_upload' => throw new RuntimeException('File upload answer belum didukung', 422),
                default => throw new RuntimeException('Tipe pertanyaan tidak valid', 422),
            };

            foreach ($answersForQuestion as $normalizedAnswer) {
                $normalizedAnswers[] = $normalizedAnswer;
            }
        }

        if ($validateRequired) {
            $this->validateRequiredQuestions($questions, $answeredQuestionIds, $normalizedAnswers);
        }

        return $normalizedAnswers;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<int, bool> $answeredQuestionIds
     * @param list<array<string, int|string>> $normalizedAnswers
     */
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
                throw new RuntimeException('Pertanyaan wajib belum dijawab: ' . $questionId, 422);
            }
        }
    }

    /**
     * @param array<string, mixed> $answer
     * @return list<array<string, int|string>>
     */
    private function normalizeFreeTextAnswer(array $answer, int $questionId): array
    {
        if (!isset($answer['answer_text']) || !\is_string($answer['answer_text'])) {
            throw new RuntimeException('Answer text harus berupa teks dan tidak boleh kosong', 422);
        }

        $answerText = trim($answer['answer_text']);

        if ($answerText === '') {
            throw new RuntimeException('Answer text harus berupa teks dan tidak boleh kosong', 422);
        }

        return [[
            'question_id' => $questionId,
            'answer_text' => $answerText,
        ]];
    }

    /**
     * @param array<string, mixed> $answer
     * @param array<int, array<int, bool>> $optionIdsByQuestionId
     * @return list<array<string, int>>
     */
    private function normalizeSingleOptionAnswer(array $answer, int $questionId, array $optionIdsByQuestionId): array
    {
        $optionId = $this->normalizePositiveInteger(
            $answer['option_id'] ?? null,
            'Option tidak valid untuk question ini'
        );

        if (!isset($optionIdsByQuestionId[$questionId][$optionId])) {
            throw new RuntimeException('Option tidak valid untuk question ini', 422);
        }

        return [[
            'question_id' => $questionId,
            'option_id' => $optionId,
        ]];
    }

    /**
     * @param array<string, mixed> $answer
     * @param array<int, array<int, bool>> $optionIdsByQuestionId
     * @return list<array<string, int>>
     */
    private function normalizeMultipleOptionAnswer(array $answer, int $questionId, array $optionIdsByQuestionId): array
    {
        $optionIds = $answer['option_ids'] ?? null;

        if (!\is_array($optionIds) || empty($optionIds)) {
            throw new RuntimeException('ID Opsi (option_ids) tidak boleh kosong', 422);
        }

        $optionIds = array_values(array_unique(array_map(
            fn (mixed $optionId): int => $this->normalizePositiveInteger($optionId, 'Option tidak valid untuk question ini'),
            $optionIds
        )));

        $normalizedAnswers = [];

        foreach ($optionIds as $optionId) {
            if (!isset($optionIdsByQuestionId[$questionId][$optionId])) {
                throw new RuntimeException('Option tidak valid untuk question ini', 422);
            }

            $normalizedAnswers[] = [
                'question_id' => $questionId,
                'option_id' => $optionId,
            ];
        }

        return $normalizedAnswers;
    }

    private function normalizePositiveInteger(mixed $value, string $message): int
    {
        if (\is_bool($value)) {
            throw new RuntimeException($message, 422);
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($integer === false) {
            throw new RuntimeException($message, 422);
        }

        return $integer;
    }

    private function normalizeCurrentPage(mixed $value): int
    {
        if (\is_bool($value)) {
            return 0;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0],
        ]);

        return $integer === false ? 0 : $integer;
    }
}
