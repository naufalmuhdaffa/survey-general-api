<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail\Update;

use App\Helpers\Response;
use App\Services\PermissionService;
use RuntimeException;

final class UpdateThumbnailController
{
    private UpdateThumbnailService $service;

    public function __construct()
    {
        $this->service = new UpdateThumbnailService();
    }

    public function update(int $surveyId): void
    {
        PermissionService::require('survey:update');

        try {
            $newThumbnailPath = $this->service->update($surveyId, $_FILES['thumbnail'] ?? null);
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
            'message' => 'Thumbnail survei berhasil diperbarui',
            'data' => ['thumbnail_path' => $newThumbnailPath]
        ], 200);
    }
}
