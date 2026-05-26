<?php

declare(strict_types=1);

namespace App\Features\User\Promote;

use App\Database;
use PDO;

final class PromoteUserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getUserById(int $userId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, role
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function updateRole(int $userId, string $role): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET role = ?
            WHERE id = ?
        ");
        $stmt->execute([$role, $userId]);
    }
}
