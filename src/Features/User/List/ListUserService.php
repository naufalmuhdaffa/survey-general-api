<?php

declare(strict_types=1);

namespace App\Features\User\List;

final class ListUserService
{
    private ListUserRepository $repository;

    public function __construct()
    {
        $this->repository = new ListUserRepository();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listManagementUsers(): array
    {
        return array_map(
            fn (array $user): array => $this->formatUser($user),
            $this->repository->getManagementUsers()
        );
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function formatUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'nik' => $user['nik'],
            'full_name' => $user['full_name'],
            'username' => $user['username'],
            'role' => $user['role'],
            'position' => $user['position'],
            'is_active' => (bool) $user['is_active'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
        ];
    }
}
