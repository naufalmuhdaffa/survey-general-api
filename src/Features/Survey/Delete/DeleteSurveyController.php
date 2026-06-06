<?php

declare(strict_types=1);

namespace App\Features\Survey\Delete;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class DeleteSurveyController
{
    private DeleteSurveyService $service;

    public function __construct()
    {
        $this->service = new DeleteSurveyService();
    }

    public function delete(int $surveyId): void
    {
        PrivilegeService::require('survey:delete');

        try {
            $this->service->delete($surveyId);
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
            'message' => 'Survei berhasil dihapus'
        ], 200);
    }
}
