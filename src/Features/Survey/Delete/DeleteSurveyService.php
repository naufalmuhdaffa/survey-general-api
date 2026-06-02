<?php

declare(strict_types=1);

namespace App\Features\Survey\Delete;

use RuntimeException;

final class DeleteSurveyService
{
    private DeleteSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new DeleteSurveyRepository();
    }

    public function delete(int $surveyId): void
    {
        if (!$this->repository->surveyExists($surveyId)) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        $this->repository->deleteSurvey($surveyId);
    }
}
