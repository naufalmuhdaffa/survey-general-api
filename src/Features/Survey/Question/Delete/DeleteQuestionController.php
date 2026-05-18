<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Delete;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class DeleteQuestionController
{
    private DeleteQuestionRepository $repository;

    public function __construct()
    {
        $this->repository = new DeleteQuestionRepository();
    }

    public function delete(int $surveyId, int $questionId): void
    {
        AuthMiddleware::handle('admin_opd', 'superadmin');

        if (!$this->repository->questionBelongsToSurvey($questionId, $surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Pertanyaan tidak ditemukan di survei ini'
            ], 404);
        }

        $this->repository->deleteQuestion($questionId);

        Response::json([
            'status' => 'success',
            'message' => 'Pertanyaan berhasil dihapus'
        ]);
    }
}