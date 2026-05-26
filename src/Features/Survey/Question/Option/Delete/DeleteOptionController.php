<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Delete;

use App\Helpers\Response;
use App\Services\PermissionService;

final class DeleteOptionController
{
    private DeleteOptionRepository $repository;

    public function __construct()
    {
        $this->repository = new DeleteOptionRepository();
    }

    public function delete(int $surveyId, int $questionId, int $optionId): void
    {
        PermissionService::require('survey_option:delete');

        if (!$this->repository->questionBelongsToSurvey($questionId, $surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Pertanyaan tidak ditemukan di survei ini'
            ], 404);
        }

        if (!$this->repository->optionBelongsToQuestion($optionId, $questionId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Opsi jawaban tidak ditemukan di pertanyaan ini'
            ], 404);
        }

        $this->repository->deleteOption($optionId);

        Response::json([
            'status' => 'success',
            'message' => 'Opsi jawaban berhasil dihapus'
        ]);
    }
}
