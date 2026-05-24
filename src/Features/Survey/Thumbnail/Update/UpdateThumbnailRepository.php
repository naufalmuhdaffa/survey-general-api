<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail\Update;

use App\Database;
use PDO;

final class UpdateThumbnailRepository
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

    public function updateThumbnailPath(int $surveyId, ?string $thumbnailPath): void
    {
        $stmt = $this->pdo->prepare("UPDATE surveys SET thumbnail_path = ? WHERE id = ?");
        $stmt->execute([$thumbnailPath, $surveyId]);

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('Gagal memperbarui thumbnail');
        }
    }
}
