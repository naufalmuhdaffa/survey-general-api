<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

final class PermissionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM user_permissions up
            JOIN permissions p ON p.id = up.permission_id
            WHERE up.user_id = ?
                AND p.code = ?
        ");
        $stmt->execute([$userId, $permission]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
