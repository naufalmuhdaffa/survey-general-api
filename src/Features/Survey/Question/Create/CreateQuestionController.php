<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Create;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class CreateQuestionController
{
    private CreateQuestionService $service;

    public function __construct()
    {
        $this->service = new CreateQuestionService();
    }

    public function create(int $surveyId): void
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
            $questionId = $this->service->create($surveyId, \is_array($data) ? $data : []);
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
            'message' => 'Pertanyaan berhasil ditambahkan',
            'data' => ['id' => $questionId]
        ], 201);
    }
}
