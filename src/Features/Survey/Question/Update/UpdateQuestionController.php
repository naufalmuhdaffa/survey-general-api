<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Update;

use App\Helpers\Response;
use App\Services\PermissionService;
use RuntimeException;

final class UpdateQuestionController
{
    private UpdateQuestionService $service;

    public function __construct()
    {
        $this->service = new UpdateQuestionService();
    }

    public function update(int $surveyId, int $questionId): void
    {
        PermissionService::require('survey:update');

        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid'
            ], 400);
        }

        try {
            $this->service->update($surveyId, $questionId, \is_array($data) ? $data : []);
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
            'message' => 'Pertanyaan berhasil diperbarui'
        ], 200);
    }
}
