<?php

declare(strict_types=1);

namespace App\Features\Survey\Delete;

use PDO;
use App\Database;

final class DeleteSurveyRepository
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

    public function deleteSurvey(int $surveyId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM surveys WHERE id = ?");
        $stmt->execute([$surveyId]);
    }
}