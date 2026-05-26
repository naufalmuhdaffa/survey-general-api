<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail\Delete;

use App\Helpers\Response;
use App\Services\FileUploadService;
use App\Services\PermissionService;

final class DeleteThumbnailController
{
    private DeleteThumbnailRepository $repository;
    private FileUploadService $fileUploadService;

    public function __construct()
    {
        $this->repository = new DeleteThumbnailRepository();
        $this->fileUploadService = new FileUploadService();
    }

    public function delete(int $surveyId): void
    {
        PermissionService::require('survey_thumbnail:delete');

        if (!$this->repository->surveyExists($surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }

        $oldThumbnailPath = $this->repository->getThumbnailPath($surveyId);

        if ($oldThumbnailPath === null) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak memiliki thumbnail yang dapat dihapus'
            ], 422);
            return;
        }

        try {
            $this->repository->clearThumbnailPath($surveyId);
        } catch (\RuntimeException $e) {
            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
            return;
        }

        $this->fileUploadService->deletePublicUpload($oldThumbnailPath);

        Response::json([
            'status' => 'success',
            'message' => 'Thumbnail survei berhasil dihapus'
        ]);
    }
}
