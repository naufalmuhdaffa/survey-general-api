<?php

declare(strict_types=1);

namespace App\Features\User\Promotable;

final class ListPromotableUserService
{
    private ListPromotableUserRepository $repository;

    public function __construct()
    {
        $this->repository = new ListPromotableUserRepository();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPromotableUsers(): array
    {
        return array_map(
            fn (array $user): array => $this->formatUser($user),
            $this->repository->getPromotableUsers()
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
