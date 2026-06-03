<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Delete;

use RuntimeException;

final class DeleteQuestionService
{
    private DeleteQuestionRepository $repository;

    public function __construct()
    {
        $this->repository = new DeleteQuestionRepository();
    }

    public function delete(int $surveyId, int $questionId): void
    {
        if (!$this->repository->questionBelongsToSurvey($questionId, $surveyId)) {
            throw new RuntimeException('Pertanyaan tidak ditemukan di survei ini', 404);
        }

        $this->repository->deleteQuestion($questionId);
    }
}
