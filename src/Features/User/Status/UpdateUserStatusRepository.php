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
            SELECT u.id, u.role_id, r.name AS role, u.is_active
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
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
