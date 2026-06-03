<?php

declare(strict_types=1);

namespace App\Features\User\Promote;

use RuntimeException;

final class PromoteUserService
{
    private PromoteUserRepository $repository;

    public function __construct()
    {
        $this->repository = new PromoteUserRepository();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function promote(int $userId, array $data): void
    {
        $role = isset($data['role']) && \is_string($data['role'])
            ? trim($data['role'])
            : '';

        if ($role !== 'admin_opd') {
            throw new RuntimeException('Promote hanya boleh ke role admin_opd', 422);
        }

        $user = $this->repository->getUserById($userId);

        if (!$user) {
            throw new RuntimeException('User tidak ditemukan', 404);
        }

        if ($user['role'] !== 'user') {
            throw new RuntimeException('Hanya user biasa yang bisa dipromosikan', 422);
        }

        $this->repository->updateRole($userId, $role);
    }
}
