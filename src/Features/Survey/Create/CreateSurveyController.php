<?php

declare(strict_types=1);

namespace App\Features\Survey\Create;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use InvalidArgumentException;
use RuntimeException;

final class CreateSurveyController
{
    private CreateSurveyService $service;

    public function __construct()
    {
        $this->service = new CreateSurveyService();
    }

    public function create(): void
    {
        $user = PrivilegeService::require('survey:create');
        $createdBy = (int) $user['id'];

        $isMultipart = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
        $data = $isMultipart ? $_POST : json_decode(file_get_contents('php://input'), true);

        if (!$isMultipart && json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid'
            ], 400);
        }

        try {
            $surveyId = $this->service->create(
                $createdBy,
                \is_array($data) ? $data : [],
                $_FILES['thumbnail'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        } catch (RuntimeException $e) {
            $statusCode = $e->getCode();

            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 500;
            }

            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $statusCode);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Survei berhasil dibuat',
            'data' => ['id' => $surveyId]
        ], 201);
    }
}
