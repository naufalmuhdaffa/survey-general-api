<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail\Update;

use App\Helpers\Response;
use App\Services\FileUploadService;
use App\Services\PermissionService;

final class UpdateThumbnailController
{
    private UpdateThumbnailRepository $repository;
    private FileUploadService $fileUploadService;

    public function __construct()
    {
        $this->repository = new UpdateThumbnailRepository();
        $this->fileUploadService = new FileUploadService();
    }

    public function update(int $surveyId): void
    {
        PermissionService::require('survey_thumbnail:update');

        if (!$this->repository->surveyExists($surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }

        if (!isset($_FILES['thumbnail'])) {
            Response::json([
                'status' => 'error',
                'message' => 'Thumbnail harus diupload'
            ], 422);
        }

        try {
            $oldThumbnailPath = $this->repository->getThumbnailPath($surveyId);
            $newThumbnailPath = $this->fileUploadService->storeSurveyThumbnail($_FILES['thumbnail']);

            try {
                $this->repository->updateThumbnailPath($surveyId, $newThumbnailPath);
            } catch (\RuntimeException $e) {
                $this->fileUploadService->deletePublicUpload($newThumbnailPath);
                throw $e;
            }
            $this->fileUploadService->deletePublicUpload($oldThumbnailPath);
        } catch (\InvalidArgumentException $e) {
            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        } catch (\RuntimeException $e) {
            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Thumbnail survei berhasil diperbarui',
            'data' => ['thumbnail_path' => $newThumbnailPath]
        ], 200);
    }
}
