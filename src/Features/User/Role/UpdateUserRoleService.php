<?php

declare(strict_types=1);

namespace App\Features\User\Role;

use RuntimeException;

final class UpdateUserRoleService
{
    private UpdateUserRoleRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateUserRoleRepository();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $currentUserId, int $userId, array $data): void
    {
        if ($currentUserId === $userId) {
            throw new RuntimeException('Akun sendiri tidak bisa mengubah role sendiri', 422);
        }

        $roleId = $this->normalizeRoleId($data['role_id'] ?? null);

        $role = $this->repository->getRoleById($roleId);

        if (!$role) {
            throw new RuntimeException('Role tidak ditemukan', 404);
        }

        if (!$this->repository->userExists($userId)) {
            throw new RuntimeException('User tidak ditemukan', 404);
        }

        $this->repository->updateRole($userId, $roleId);
    }

    private function normalizeRoleId(mixed $roleId): int
    {
        if (\is_bool($roleId)) {
            throw new RuntimeException('Role id (role_id) harus berupa bilangan bulat lebih dari 0', 422);
        }

        $roleId = filter_var($roleId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($roleId === false) {
            throw new RuntimeException('Role id (role_id) harus berupa bilangan bulat lebih dari 0', 422);
        }

        return $roleId;
    }
}
