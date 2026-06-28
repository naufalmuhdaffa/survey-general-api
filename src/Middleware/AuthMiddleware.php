<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Repositories\AuthRepository;
use App\Helpers\Response;
use App\Services\CsrfService;
use App\Services\JwtService;

final class AuthMiddleware
{
    public static function handle(string ...$roles): array
    {
        $token = JwtService::token();

        if ($token === null) {
            Response::json([
                'status' => 'error',
                'message' => 'Token tidak ditemukan'
            ], 401);
        }

        $payload = JwtService::verify($token);

        if ($payload === null) {
            Response::json([
                'status' => 'error',
                'message' => 'Token tidak valid atau sudah kedaluwarsa'
            ], 401);
        }

        $repository = new AuthRepository();

        if ($repository->isTokenRevoked($token)) {
            Response::json([
                'status' => 'error',
                'message' => 'Token sudah tidak berlaku'
            ], 401);
        }

        CsrfService::enforceForUnsafeRequest($payload);

        $userId = (int) ($payload->data->userId ?? 0);

        if ($userId <= 0) {
            Response::json([
                'status' => 'error',
                'message' => 'Token tidak valid'
            ], 401);
        }

        $user = $repository->findById($userId);

        if (!$user) {
            Response::json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 401);
        }

        $effectiveRole = self::determineRole($user['role'], (bool) $user['is_active']);
        $effectiveRoleId = $effectiveRole === $user['role']
            ? (int) $user['role_id']
            : (int) $user['default_role_id'];

        if (!empty($roles) && !\in_array($effectiveRole, $roles, true)) {
            Response::json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses'
            ], 403);
        }

        $user['effective_role'] = $effectiveRole;
        $user['effective_role_id'] = $effectiveRoleId;

        return $user;
    }

    private static function determineRole(string $role, bool $isActive): string
    {
        if (!$isActive && $role !== 'user') {
            return 'user';
        }

        return $role;
    }
}
