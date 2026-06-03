<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail\Delete;

use App\Services\FileUploadService;
use RuntimeException;

final class DeleteThumbnailService
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
        if (!$this->repository->surveyExists($surveyId)) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        $oldThumbnailPath = $this->repository->getThumbnailPath($surveyId);

        if ($oldThumbnailPath === null) {
            throw new RuntimeException('Survei tidak memiliki thumbnail yang dapat dihapus', 422);
        }

        try {
            $this->repository->clearThumbnailPath($surveyId);
        } catch (RuntimeException $e) {
            throw new RuntimeException($e->getMessage(), 500);
        }

        $this->fileUploadService->deletePublicUpload($oldThumbnailPath);
    }
}
