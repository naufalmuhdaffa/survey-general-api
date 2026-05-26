<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Database;
use App\Helpers\Response;
use App\Services\JwtService;

final class AuthMiddleware
{
    public static function handle(string ...$roles): array
    {
        $token = JwtService::bearerToken();

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

        $userId = (int) ($payload->data->userId ?? 0);

        if ($userId <= 0) {
            Response::json([
                'status' => 'error',
                'message' => 'Token tidak valid'
            ], 401);
        }

        $stmt = Database::connection()->prepare("
            SELECT id, username, role, position, is_active
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 401);
        }

        $effectiveRole = self::effectiveRole($user['role'], (bool) $user['is_active']);

        if (!empty($roles) && !\in_array($effectiveRole, $roles, true)) {
            Response::json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses'
            ], 403);
        }

        $user['effective_role'] = $effectiveRole;

        return $user;
    }

    private static function effectiveRole(string $role, bool $isActive): string
    {
        if (!$isActive && \in_array($role, ['superadmin', 'admin_opd'], true)) {
            return 'user';
        }

        return $role;
    }
}
