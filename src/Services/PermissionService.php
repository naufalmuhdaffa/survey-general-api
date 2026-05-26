<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class PermissionService
{
    public static function require(string $permission): array
    {
        $user = AuthMiddleware::handle('admin_opd', 'superadmin');

        if ($user['effective_role'] === 'superadmin') {
            return $user;
        }

        if (!self::hasPermission((int) $user['id'], $permission)) {
            Response::json([
                'status' => 'error',
                'message' => 'Privilege tidak cukup: ' . $permission
            ], 403);
        }

        return $user;
    }

    private static function hasPermission(int $userId, string $permission): bool
    {
        $stmt = Database::connection()->prepare("
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
