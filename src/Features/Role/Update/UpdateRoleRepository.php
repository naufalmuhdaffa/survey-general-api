<?php

declare(strict_types=1);

namespace App\Features\Role\Update;

use App\Database;
use PDO;

final class UpdateRoleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getRoleById(int $roleId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, name, created_at, updated_at
            FROM roles
            WHERE id = ?
        ");
        $stmt->execute([$roleId]);
        return $stmt->fetch();
    }

    public function roleNameExists(string $name, int $exceptRoleId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM roles WHERE name = ? AND id <> ?");
        $stmt->execute([$name, $exceptRoleId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function updateRole(int $roleId, string $name): void
    {
        $stmt = $this->pdo->prepare("UPDATE roles SET name = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $roleId]);
    }
}
