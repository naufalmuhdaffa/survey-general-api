<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Update;

use RuntimeException;
use function array_key_exists;

final class UpdateQuestionService
{
    private const array VALID_QUESTION_TYPES = [
        'free_text',
        'radio_button',
        'checkbox',
        'dropdown',
        'rating_scale',
        'file_upload',
    ];

    private UpdateQuestionRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateQuestionRepository();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $surveyId, int $questionId, array $data): void
    {
        if (!$this->repository->questionBelongsToSurvey($questionId, $surveyId)) {
            throw new RuntimeException('Pertanyaan tidak ditemukan di survei ini', 404);
        }

        $allowedFields = ['question_text', 'question_type', 'is_required', 'question_order', 'page', 'parent_option_id'];
        $fields = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            throw new RuntimeException('Tidak ada field yang diupdate', 422);
        }

        if (array_key_exists('question_text', $fields)) {
            if (!\is_string($fields['question_text']) || trim($fields['question_text']) === '') {
                throw new RuntimeException('Teks pertanyaan tidak boleh kosong', 422);
            }

            $fields['question_text'] = trim($fields['question_text']);
        }

        if (array_key_exists('question_type', $fields)) {
            if (!\is_string($fields['question_type']) || !\in_array($fields['question_type'], self::VALID_QUESTION_TYPES, true)) {
                throw new RuntimeException('Tipe pertanyaan tidak valid', 422);
            }
        }

        if (array_key_exists('is_required', $fields)) {
            $fields['is_required'] = $this->normalizeIsRequired($fields['is_required']);
        }

        if (array_key_exists('question_order', $fields)) {
            $fields['question_order'] = $this->normalizePositiveInteger(
                $fields['question_order'],
                'Value urutan pertanyaan (question_order) harus berupa bilangan bulat lebih dari 0'
            );
        }

        if (array_key_exists('page', $fields)) {
            $fields['page'] = $this->normalizePositiveInteger(
                $fields['page'],
                'Value halaman (page) harus berupa bilangan bulat lebih dari 0'
            );
        }

        if (array_key_exists('parent_option_id', $fields)) {
            $fields['parent_option_id'] = $fields['parent_option_id'] === null
                ? null
                : $this->normalizePositiveInteger(
                    $fields['parent_option_id'],
                    'Parent option id (parent_option_id) harus berupa bilangan bulat lebih dari 0'
                );
        }

        $this->repository->updateQuestion($questionId, $fields);
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
}
