<?php

declare(strict_types=1);

namespace App\Features\Role\Update;

use RuntimeException;

final class UpdateRoleService
{
    private UpdateRoleRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateRoleRepository();
    }

    public function update(int $roleId, array $data): void
    {
        $role = $this->getEditableRole($roleId);
        $name = $this->normalizeRoleName($data['name'] ?? null);

        if ($name === 'user') {
            throw new RuntimeException('Role tidak boleh diubah menjadi user', 422);
        }

        if ($this->repository->roleNameExists($name, $roleId)) {
            throw new RuntimeException('Role sudah ada', 409);
        }

        $this->repository->updateRole((int) $role['id'], $name);
    }

    private function getEditableRole(int $roleId): array
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

    private function normalizeRoleName(mixed $name): string
    {
        $name = \is_string($name) ? strtolower(trim($name)) : '';

        if ($name === '') {
            throw new RuntimeException('Nama role harus diisi', 422);
        }

        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new RuntimeException('Nama role hanya boleh huruf kecil, angka, dan underscore', 422);
        }

        return $name;
    }
}
