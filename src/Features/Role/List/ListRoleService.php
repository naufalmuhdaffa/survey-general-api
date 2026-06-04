<?php

declare(strict_types=1);

namespace App\Features\Role\List;

final class ListRoleService
{
    private ListRoleRepository $repository;

    public function __construct()
    {
        $this->repository = new ListRoleRepository();
    }

    public function list(): array
    {
        return array_map(
            fn (array $role): array => $this->formatRole($role),
            $this->repository->getRoles()
        );
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
