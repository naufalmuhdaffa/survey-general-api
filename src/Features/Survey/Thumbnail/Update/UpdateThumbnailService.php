<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail\Update;

use App\Services\FileUploadService;
use InvalidArgumentException;
use RuntimeException;

final class UpdateThumbnailService
{
    private UpdateThumbnailRepository $repository;
    private FileUploadService $fileUploadService;

    public function __construct()
    {
        $this->repository = new UpdateThumbnailRepository();
        $this->fileUploadService = new FileUploadService();
    }

    public function update(int $surveyId, ?array $thumbnail): string
    {
        if (!$this->repository->surveyExists($surveyId)) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        if ($thumbnail === null) {
            throw new RuntimeException('Thumbnail harus diupload', 422);
        }

        try {
            $oldThumbnailPath = $this->repository->getThumbnailPath($surveyId);
            $newThumbnailPath = $this->fileUploadService->storeSurveyThumbnail($thumbnail);
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            throw new RuntimeException($e->getMessage(), 500);
        }

        try {
            $this->repository->updateThumbnailPath($surveyId, $newThumbnailPath);
        } catch (RuntimeException $e) {
            $this->fileUploadService->deletePublicUpload($newThumbnailPath);
            throw new RuntimeException($e->getMessage(), 500);
        }

        $this->fileUploadService->deletePublicUpload($oldThumbnailPath);

        return $newThumbnailPath;
    }
}
