<?php

declare(strict_types=1);

namespace App\Features\User\Status;

use App\Database;
use PDO;

final class UpdateUserStatusRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getUserById(int $userId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, role, is_active
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function countActiveSuperadmins(): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM users
            WHERE role = 'superadmin'
                AND is_active = TRUE
        ");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function updateStatus(int $userId, bool $isActive): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$isActive ? 1 : 0, $userId]);
    }
}
