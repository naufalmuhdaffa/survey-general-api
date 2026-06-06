<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\Update;

use RuntimeException;
use Throwable;

final class UpdateRolePrivilegeService
{
    private UpdateRolePrivilegeRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateRolePrivilegeRepository();
    }

    public function update(int $roleId, array $data): void
    {
        $role = $this->getManageableRole($roleId);
        $privileges = $this->normalizePrivileges($data['privileges'] ?? null);
        $unknownPrivileges = $this->repository->getUnknownPrivilegeNames($privileges);

        if (!empty($unknownPrivileges)) {
            throw new RuntimeException('Privilege tidak ditemukan: ' . implode(', ', $unknownPrivileges), 422);
        }

        try {
            $this->repository->replaceRolePrivileges((int) $role['id'], $privileges);
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal memperbarui privilege role', 500, $e);
        }
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

    private function normalizePrivileges(mixed $privileges): array
    {
        if (!\is_array($privileges)) {
            throw new RuntimeException('privileges harus berupa array', 422);
        }

        $normalizedPrivileges = [];

        foreach ($privileges as $privilege) {
            if (!\is_string($privilege) || trim($privilege) === '') {
                throw new RuntimeException('privileges harus berisi privilege name yang valid', 422);
            }

            $normalizedPrivileges[] = trim($privilege);
        }

        return array_values(array_unique($normalizedPrivileges));
    }
}
