<?php

declare(strict_types=1);

namespace App\Features\Survey\Delete;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class DeleteSurveyController
{
    private DeleteSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new DeleteSurveyRepository();
    }

    public function delete(int $surveyId): void
    {
        AuthMiddleware::handle('admin_opd', 'superadmin');

        if (!$this->repository->surveyExists($surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }

        $this->repository->deleteSurvey($surveyId);

        Response::json([
            'status' => 'success',
            'message' => 'Survei berhasil dihapus'
        ]);
    }
}