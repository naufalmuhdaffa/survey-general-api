<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Create;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class CreateOptionController
{
    private CreateOptionService $service;

    public function __construct()
    {
        $this->service = new CreateOptionService();
    }

    public function create(int $surveyId, int $questionId): void
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
            $optionId = $this->service->create($surveyId, $questionId, \is_array($data) ? $data : []);
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
            'message' => 'Opsi jawaban berhasil ditambahkan',
            'data' => ['id' => $optionId]
        ], 201);
    }
}
