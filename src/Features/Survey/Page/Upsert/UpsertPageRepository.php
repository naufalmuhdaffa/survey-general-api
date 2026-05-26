<?php

declare(strict_types=1);

namespace App\Features\Survey\Page\Upsert;

use App\Database;
use PDO;

final class UpsertPageRepository
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

    public function upsertPageSection(int $surveyId, int $page, ?string $section): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO survey_pages (survey_id, page, section)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE section = VALUES(section)
        ");
        $stmt->execute([$surveyId, $page, $section]);
    }
}
