<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\Detail;

use RuntimeException;

final class DetailRolePrivilegeService
{
    private DetailRolePrivilegeRepository $repository;

    public function __construct()
    {
        $this->repository = new DetailRolePrivilegeRepository();
    }

    public function detail(int $roleId): array
    {
        $role = $this->getManageableRole($roleId);

        return [
            'role' => $this->formatRole($role),
            'privileges' => $this->repository->getRolePrivilegeNames($roleId),
        ];
    }

    private function getManageableRole(int $roleId): array
    {
        $role = $this->repository->getRoleById($roleId);

        if (!$role) {
            throw new RuntimeException('Role tidak ditemukan', 404);
        }

        if ($role['name'] === 'user') {
            throw new RuntimeException('Role user tidak bisa dikelola', 422);
        }

        return $role;
    }

    private function formatRole(array $role): array
    {
        return [
            'id' => (int) $role['id'],
            'name' => $role['name'],
            'created_at' => $role['created_at'],
            'updated_at' => $role['updated_at'],
        ];
    }
}
