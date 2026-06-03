<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail\Delete;

use App\Helpers\Response;
use App\Services\PermissionService;
use RuntimeException;

final class DeleteThumbnailController
{
    private DeleteThumbnailService $service;

    public function __construct()
    {
        $this->service = new DeleteThumbnailService();
    }

    public function delete(int $surveyId): void
    {
        PermissionService::require('survey:update');

        try {
            $this->service->delete($surveyId);
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
            'message' => 'Thumbnail survei berhasil dihapus'
        ], 200);
    }
}
