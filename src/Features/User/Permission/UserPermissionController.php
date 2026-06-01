<?php

declare(strict_types=1);

namespace App\Features\User\Permission;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class UserPermissionController
{
    private UserPermissionRepository $repository;

    public function __construct()
    {
        $this->repository = new UserPermissionRepository();
    }

    public function listPermissions(): void
    {
        AuthMiddleware::handle('superadmin');

        Response::json([
            'status' => 'success',
            'data' => $this->repository->getAllPermissions()
        ], 200);
    }

    public function getUserPermissions(int $userId): void
    {
        AuthMiddleware::handle('superadmin');

        $user = $this->repository->getUserById($userId);

        if (!$user) {
            Response::json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        if ($user['role'] !== 'admin_opd') {
            Response::json([
                'status' => 'error',
                'message' => 'Privilege hanya bisa diatur untuk admin OPD'
            ], 422);
        }

        Response::json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => (int) $user['id'],
                    'full_name' => $user['full_name'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'is_active' => (bool) $user['is_active'],
                ],
                'permissions' => $this->repository->getUserPermissionCodes($userId),
            ]
        ], 200);
    }

    public function updateUserPermissions(int $userId): void
    {
        AuthMiddleware::handle('superadmin');

        $user = $this->repository->getUserById($userId);

        if (!$user) {
            Response::json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        if ($user['role'] !== 'admin_opd') {
            Response::json([
                'status' => 'error',
                'message' => 'Privilege hanya bisa diatur untuk admin OPD'
            ], 422);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $permissions = $data['permissions'] ?? null;

        if (!\is_array($permissions)) {
            Response::json([
                'status' => 'error',
                'message' => 'permissions harus berupa array'
            ], 422);
        }

        $normalizedPermissions = [];

        foreach ($permissions as $permission) {
            if (!\is_string($permission) || trim($permission) === '') {
                Response::json([
                    'status' => 'error',
                    'message' => 'permissions harus berisi privilege code yang valid'
                ], 422);
            }

            $normalizedPermissions[] = trim($permission);
        }

        $permissions = array_values(array_unique($normalizedPermissions));
        $unknownPermissions = $this->repository->getUnknownPermissionCodes($permissions);

        if (!empty($unknownPermissions)) {
            Response::json([
                'status' => 'error',
                'message' => 'Privilege tidak ditemukan: ' . implode(', ', $unknownPermissions)
            ], 422);
        }

        $this->repository->replaceUserPermissions($userId, $permissions);

        Response::json([
            'status' => 'success',
            'message' => 'Privilege user berhasil diperbarui'
        ], 200);
    }
}
