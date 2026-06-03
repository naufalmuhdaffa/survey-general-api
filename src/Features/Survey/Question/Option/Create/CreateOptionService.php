<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Create;

use RuntimeException;

final class CreateOptionService
{
    private CreateOptionRepository $repository;

    public function __construct()
    {
        $this->repository = new CreateOptionRepository();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $surveyId, int $questionId, array $data): int
    {
        if (!$this->repository->questionExists($questionId)) {
            throw new RuntimeException('Pertanyaan tidak ditemukan', 404);
        }

        if (!$this->repository->questionBelongsToSurvey($questionId, $surveyId)) {
            throw new RuntimeException('Pertanyaan tidak ditemukan di survei ini', 404);
        }

        if (!$this->repository->questionTypeAllowsOptions($questionId)) {
            throw new RuntimeException('Tipe pertanyaan ini tidak mendukung opsi jawaban', 422);
        }

        $optionText = isset($data['option_text']) && \is_string($data['option_text'])
            ? trim($data['option_text'])
            : '';

        if ($optionText === '') {
            throw new RuntimeException('Teks opsi jawaban harus diisi', 422);
        }

        $optionOrder = $this->repository->getNextOptionOrder($questionId);

        return $this->repository->createOption($questionId, $optionText, $optionOrder);
    }
}
