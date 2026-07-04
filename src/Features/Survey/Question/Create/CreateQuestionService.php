<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Create;

use RuntimeException;

final class CreateQuestionService
{
    private const array VALID_QUESTION_TYPES = [
        'free_text',
        'radio_button',
        'checkbox',
        'dropdown',
        'rating_scale',
        'file_upload',
    ];

    private CreateQuestionRepository $repository;

    public function __construct()
    {
        $this->repository = new CreateQuestionRepository();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $surveyId, array $data): int
    {
        if (!$this->repository->surveyExists($surveyId)) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        if (!$this->repository->surveyIsDraft($surveyId)) {
            throw new RuntimeException('Isi survey tidak dapat diubah setelah dipublikasikan', 409);
        }

        $questionText = isset($data['question_text']) && \is_string($data['question_text'])
            ? trim($data['question_text'])
            : '';

        if ($questionText === '') {
            throw new RuntimeException('Teks pertanyaan harus diisi', 422);
        }

        $questionType = isset($data['question_type']) && \is_string($data['question_type'])
            ? $data['question_type']
            : '';

        if (!\in_array($questionType, self::VALID_QUESTION_TYPES, true)) {
            throw new RuntimeException('Tipe pertanyaan tidak valid', 422);
        }

        $isRequired = $this->normalizeIsRequired($data['is_required'] ?? false);
        $page = $this->normalizePositiveInteger($data['page'] ?? 1, 'Page harus lebih dari 0');
        $parentOptionId = $this->normalizeOptionalParentOptionId($data['parent_option_id'] ?? null);

        if (
            $parentOptionId !== null
            && !$this->repository->optionBelongsToSurvey($parentOptionId, $surveyId)
        ) {
            throw new RuntimeException('Opsi induk pertanyaan kondisional tidak valid', 422);
        }

        $questionOrder = $this->repository->getNextQuestionOrder($surveyId);

        return $this->repository->createQuestion(
            $surveyId,
            $questionText,
            $questionType,
            $isRequired,
            $questionOrder,
            $page,
            $parentOptionId
        );
    }

    private function normalizeIsRequired(mixed $isRequired): bool
    {
        if (\is_bool($isRequired)) {
            return $isRequired;
        }

        if (\is_int($isRequired) && \in_array($isRequired, [0, 1], true)) {
            return (bool) $isRequired;
        }

        if (\is_string($isRequired)) {
            $isRequired = strtolower(trim($isRequired));

            if (\in_array($isRequired, ['1', 'true'], true)) {
                return true;
            }

            if (\in_array($isRequired, ['0', 'false', ''], true)) {
                return false;
            }
        }

        throw new RuntimeException('Field wajib diisi (is_required) harus berupa boolean', 422);
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

    private function normalizeOptionalParentOptionId(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (\is_string($value) && trim($value) === '') {
            return null;
        }

        return $this->normalizePositiveInteger(
            $value,
            'Opsi induk pertanyaan kondisional tidak valid'
        );
    }
}
