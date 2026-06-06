<?php

declare(strict_types=1);

namespace App\Features\Survey\Page\UpdateSection;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class UpdatePageSectionController
{
    private UpdatePageSectionService $service;

    public function __construct()
    {
        $this->service = new UpdatePageSectionService();
    }

    public function update(int $surveyId, int $page): void
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
            $pageSection = $this->service->update($surveyId, $page, $data);
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
