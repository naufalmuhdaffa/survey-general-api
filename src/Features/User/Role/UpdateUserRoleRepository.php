<?php

declare(strict_types=1);

namespace App\Features\User\Role;

use App\Database;
use PDO;

final class UpdateUserRoleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function userExists(int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getRoleById(int $roleId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, name
            FROM roles
            WHERE id = ?
        ");
        $stmt->execute([$roleId]);
        return $stmt->fetch();
    }

    public function updateRole(int $userId, int $roleId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET role_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$roleId, $userId]);
    }
}
