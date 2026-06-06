<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Update;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class UpdateOptionController
{
    private UpdateOptionService $service;

    public function __construct()
    {
        $this->service = new UpdateOptionService();
    }

    public function update(int $surveyId, int $questionId, int $optionId): void
    {
        PrivilegeService::require('survey:update');

        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid'
            ], 400);
        }

        try {
            $this->service->update($surveyId, $questionId, $optionId, \is_array($data) ? $data : []);
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
            'message' => 'Opsi jawaban berhasil diperbarui'
        ], 200);
    }
}
