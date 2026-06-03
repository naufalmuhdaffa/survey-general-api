<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Delete;

use App\Helpers\Response;
use App\Services\PermissionService;
use RuntimeException;

final class DeleteQuestionController
{
    private DeleteQuestionService $service;

    public function __construct()
    {
        $this->service = new DeleteQuestionService();
    }

    public function delete(int $surveyId, int $questionId): void
    {
        PermissionService::require('survey:update');

        try {
            $this->service->delete($surveyId, $questionId);
        } catch (RuntimeException $e) {
            $statusCode = $e->getCode();

            if ($statusCode < 400 || $statusCode > 599) {
                throw $e;
            }

            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $statusCode);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Pertanyaan berhasil dihapus'
        ], 200);
    }
}
