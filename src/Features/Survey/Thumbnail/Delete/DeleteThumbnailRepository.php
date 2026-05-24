<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail\Delete;

use App\Database;
use PDO;

final class DeleteThumbnailRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function surveyExists(int $surveyId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM surveys WHERE id = ?");
        $stmt->execute([$surveyId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getThumbnailPath(int $surveyId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT thumbnail_path FROM surveys WHERE id = ?");
        $stmt->execute([$surveyId]);
        $thumbnailPath = $stmt->fetchColumn();

        return $thumbnailPath === false ? null : $thumbnailPath;
    }

    public function clearThumbnailPath(int $surveyId): void
    {
        $stmt = $this->pdo->prepare("UPDATE surveys SET thumbnail_path = NULL WHERE id = ?");
        $stmt->execute([$surveyId]);

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('Gagal menghapus thumbnail: survei tidak ditemukan');
        }
    }
}
