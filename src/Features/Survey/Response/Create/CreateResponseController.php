<?php

declare(strict_types=1);

namespace App\Features\Survey\Response\Create;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use RuntimeException;

final class CreateResponseController
{
    private CreateResponseService $service;

    public function __construct()
    {
        $this->service = new CreateResponseService();
    }

    public function create(int $surveyId): void
    {
        $user = AuthMiddleware::handle();
        $userId = (int) $user['id'];
        $position = (string) $user['position'];

        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid'
            ], 400);
        }

        try {
            $responseId = $this->service->create($surveyId, $userId, $position, \is_array($data) ? $data : []);
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
            'message' => 'Response survei berhasil disimpan',
            'data' => ['id' => $responseId]
        ], 201);
    }

    public function detail(int $surveyId): void
    {
        $user = AuthMiddleware::handle();
        $userId = (int) $user['id'];
        $position = (string) $user['position'];

        try {
            $response = $this->service->detail($surveyId, $userId, $position);
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
            'data' => $response
        ], 200);
    }

    public function saveDraft(int $surveyId): void
    {
        $user = AuthMiddleware::handle();
        $userId = (int) $user['id'];
        $position = (string) $user['position'];

        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid'
            ], 400);
        }

        try {
            $response = $this->service->saveDraft($surveyId, $userId, $position, \is_array($data) ? $data : []);
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
            'message' => 'Draft response berhasil disimpan',
            'data' => $response
        ], 200);
    }
}
