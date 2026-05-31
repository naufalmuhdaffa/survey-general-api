<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PermissionRepository;
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

        $repository = new PermissionRepository();

        if (!$repository->hasPermission((int) $user['id'], $permission)) {
            Response::json([
                'status' => 'error',
                'message' => 'Privilege tidak cukup: ' . $permission
            ], 403);
        }

        return $user;
    }
}
