<?php

declare(strict_types=1);

namespace App\Features\Role\Delete;

use App\Database;
use PDO;
use Throwable;

final class DeleteRoleRepository
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

    public function deleteRoleAndMoveUsersToDefault(int $roleId): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("SELECT id FROM roles WHERE name = 'user'");
            $stmt->execute();
            $defaultRoleId = (int) $stmt->fetchColumn();

            $stmt = $this->pdo->prepare("UPDATE users SET role_id = ? WHERE role_id = ?");
            $stmt->execute([$defaultRoleId, $roleId]);

            $stmt = $this->pdo->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->execute([$roleId]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
