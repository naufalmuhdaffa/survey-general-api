<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Delete;

use RuntimeException;

final class DeleteOptionService
{
    private DeleteOptionRepository $repository;

    public function __construct()
    {
        $this->repository = new DeleteOptionRepository();
    }

    public function delete(int $surveyId, int $questionId, int $optionId): void
    {
        if (!$this->repository->questionBelongsToSurvey($questionId, $surveyId)) {
            throw new RuntimeException('Pertanyaan tidak ditemukan di survei ini', 404);
        }

        if (!$this->repository->optionBelongsToQuestion($optionId, $questionId)) {
            throw new RuntimeException('Opsi jawaban tidak ditemukan di pertanyaan ini', 404);
        }

        $this->repository->deleteOption($optionId);
    }
}
