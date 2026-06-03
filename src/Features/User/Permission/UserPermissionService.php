<?php

declare(strict_types=1);

namespace App\Features\User\Permission;

use RuntimeException;
use Throwable;

final class UserPermissionService
{
    private UserPermissionRepository $repository;

    public function __construct()
    {
        $this->repository = new UserPermissionRepository();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPermissions(): array
    {
        return $this->repository->getAllPermissions();
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserPermissions(int $userId): array
    {
        $user = $this->getAdminOpdUser($userId);

        return [
            'user' => [
                'id' => (int) $user['id'],
                'full_name' => $user['full_name'],
                'username' => $user['username'],
                'role' => $user['role'],
                'is_active' => (bool) $user['is_active'],
            ],
            'permissions' => $this->repository->getUserPermissionCodes($userId),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateUserPermissions(int $userId, array $data): void
    {
        $this->getAdminOpdUser($userId);

        $permissions = $this->normalizePermissions($data['permissions'] ?? null);
        $unknownPermissions = $this->repository->getUnknownPermissionCodes($permissions);

        if (!empty($unknownPermissions)) {
            throw new RuntimeException('Privilege tidak ditemukan: ' . implode(', ', $unknownPermissions), 422);
        }

        try {
            $this->repository->replaceUserPermissions($userId, $permissions);
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal memperbarui privilege user', 500, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getAdminOpdUser(int $userId): array
    {
        $user = $this->repository->getUserById($userId);

        if (!$user) {
            throw new RuntimeException('User tidak ditemukan', 404);
        }

        if ($user['role'] !== 'admin_opd') {
            throw new RuntimeException('Privilege hanya bisa diatur untuk admin OPD', 422);
        }

        return $user;
    }

    /**
     * @return list<string>
     */
    private function normalizePermissions(mixed $permissions): array
    {
        if (!\is_array($permissions)) {
            throw new RuntimeException('permissions harus berupa array', 422);
        }

        $normalizedPermissions = [];

        foreach ($permissions as $permission) {
            if (!\is_string($permission) || trim($permission) === '') {
                throw new RuntimeException('permissions harus berisi privilege code yang valid', 422);
            }

            $normalizedPermissions[] = trim($permission);
        }

        return array_values(array_unique($normalizedPermissions));
    }
}
