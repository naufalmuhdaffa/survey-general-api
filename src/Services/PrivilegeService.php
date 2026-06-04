<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Repositories\PrivilegeRepository;

final class PrivilegeService
{
    public static function require(string $privilege): array
    {
        $user = AuthMiddleware::handle();
        $repository = new PrivilegeRepository();

        if (!$repository->hasPrivilege((int) $user['effective_role_id'], $privilege)) {
            Response::json([
                'status' => 'error',
                'message' => 'Privilege tidak cukup: ' . $privilege
            ], 403);
        }

        return $user;
    }
}
