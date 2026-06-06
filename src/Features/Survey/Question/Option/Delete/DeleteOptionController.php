<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Delete;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class DeleteOptionController
{
    private DeleteOptionService $service;

    public function __construct()
    {
        $this->service = new DeleteOptionService();
    }

    public function delete(int $surveyId, int $questionId, int $optionId): void
    {
        PrivilegeService::require('survey:update');

        try {
            $this->service->delete($surveyId, $questionId, $optionId);
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
            'message' => 'Opsi jawaban berhasil dihapus'
        ], 200);
    }
}
