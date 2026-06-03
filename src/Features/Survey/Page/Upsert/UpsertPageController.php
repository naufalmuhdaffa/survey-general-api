<?php

declare(strict_types=1);

namespace App\Features\Survey\Page\Upsert;

use App\Helpers\Response;
use App\Services\PermissionService;
use RuntimeException;

final class UpsertPageController
{
    private UpsertPageService $service;

    public function __construct()
    {
        $this->service = new UpsertPageService();
    }

    public function upsert(int $surveyId, int $page): void
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
            $pageSection = $this->service->upsert($surveyId, $page, $data);
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
            'message' => 'Section halaman survei berhasil diperbarui',
            'data' => $pageSection
        ], 200);
    }
}
