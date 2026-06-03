<?php

declare(strict_types=1);

namespace App\Features\User\Permission;

use App\Database;
use PDO;

final class UserPermissionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getAllPermissions(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT code, description
            FROM permissions
            ORDER BY code ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUserById(int $userId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, full_name, username, role, is_active
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function getUserPermissionCodes(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.code
            FROM user_permissions up
            JOIN permissions p ON p.id = up.permission_id
            WHERE up.user_id = ?
            ORDER BY p.code ASC
        ");
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'code');
    }

    public function getUnknownPermissionCodes(array $permissionCodes): array
    {
        if (empty($permissionCodes)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($permissionCodes), '?'));
        $stmt = $this->pdo->prepare("
            SELECT code
            FROM permissions
            WHERE code IN ($placeholders)
        ");
        $stmt->execute($permissionCodes);

        $knownPermissions = array_column($stmt->fetchAll(), 'code');
        return array_values(array_diff($permissionCodes, $knownPermissions));
    }

    public function replaceUserPermissions(int $userId, array $permissionCodes): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?");
            $stmt->execute([$userId]);

            if (!empty($permissionCodes)) {
                $placeholders = implode(',', array_fill(0, \count($permissionCodes), '?'));
                $stmt = $this->pdo->prepare("
                    SELECT id
                    FROM permissions
                    WHERE code IN ($placeholders)
                ");
                $stmt->execute($permissionCodes);
                $permissionIds = array_column($stmt->fetchAll(), 'id');

                $insertPlaceholders = implode(', ', array_fill(0, \count($permissionIds), '(?, ?)'));
                $values = [];

                foreach ($permissionIds as $permissionId) {
                    $values[] = $userId;
                    $values[] = (int) $permissionId;
                }

                $stmt = $this->pdo->prepare("
                    INSERT INTO user_permissions (user_id, permission_id)
                    VALUES $insertPlaceholders
                ");
                $stmt->execute($values);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
