<?php

declare(strict_types=1);

namespace App\Features\Role\Create;

use RuntimeException;

final class CreateRoleService
{
    private CreateRoleRepository $repository;

    public function __construct()
    {
        $this->repository = new CreateRoleRepository();
    }

    public function create(array $data): int
    {
        $name = $this->normalizeRoleName($data['name'] ?? null);

        if ($name === 'user') {
            throw new RuntimeException('Role user tidak boleh dibuat manual', 422);
        }

        if ($this->repository->roleNameExists($name)) {
            throw new RuntimeException('Role sudah ada', 409);
        }

        return $this->repository->createRole($name);
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
