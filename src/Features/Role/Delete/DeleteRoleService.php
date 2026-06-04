<?php

declare(strict_types=1);

namespace App\Features\Role\Delete;

use RuntimeException;
use Throwable;

final class DeleteRoleService
{
    private DeleteRoleRepository $repository;

    public function __construct()
    {
        $this->repository = new DeleteRoleRepository();
    }

    public function delete(int $roleId): void
    {
        $role = $this->repository->getRoleById($roleId);

        if (!$role) {
            throw new RuntimeException('Role tidak ditemukan', 404);
        }

        if ($role['name'] === 'user') {
            throw new RuntimeException('Role user tidak bisa dikelola', 422);
        }

        try {
            $this->repository->deleteRoleAndMoveUsersToDefault((int) $role['id']);
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal menghapus role', 500, $e);
        }
    }
}
