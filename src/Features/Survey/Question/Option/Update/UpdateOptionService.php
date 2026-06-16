<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Update;

use RuntimeException;

final class UpdateOptionService
{
    private UpdateOptionRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateOptionRepository();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $surveyId, int $questionId, int $optionId, array $data): void
    {
        if (!$this->repository->optionExists($optionId)) {
            throw new RuntimeException('Opsi jawaban tidak ditemukan', 404);
        }

        if (!$this->repository->questionBelongsToSurvey($questionId, $surveyId)) {
            throw new RuntimeException('Pertanyaan tidak ditemukan di survei ini', 404);
        }

        if (!$this->repository->surveyIsDraft($surveyId)) {
            throw new RuntimeException('Isi survey tidak dapat diubah setelah dipublikasikan', 409);
        }

        if (!$this->repository->optionBelongsToQuestion($optionId, $questionId)) {
            throw new RuntimeException('Opsi jawaban tidak ditemukan di pertanyaan ini', 404);
        }

        $allowedFields = ['option_text', 'option_order'];
        $fields = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            throw new RuntimeException('Tidak ada field yang diupdate', 422);
        }

        if (isset($fields['option_text'])) {
            if (!\is_string($fields['option_text']) || trim($fields['option_text']) === '') {
                throw new RuntimeException('Teks opsi jawaban tidak boleh kosong', 422);
            }

            $fields['option_text'] = trim($fields['option_text']);
        }

        if (isset($fields['option_order'])) {
            $optionOrder = filter_var($fields['option_order'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);

            if ($optionOrder === false) {
                throw new RuntimeException('Value urutan opsi (option_order) harus berupa bilangan bulat lebih dari 0', 422);
            }

            $fields['option_order'] = $optionOrder;
        }

        $this->repository->updateOption($optionId, $fields);
    }
}
