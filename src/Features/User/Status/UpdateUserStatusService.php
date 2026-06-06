<?php

declare(strict_types=1);

namespace App\Features\User\Status;

use RuntimeException;

final class UpdateUserStatusService
{
    private UpdateUserStatusRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateUserStatusRepository();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $currentUserId, int $targetUserId, array $data): void
    {
        if (!\array_key_exists('is_active', $data)) {
            throw new RuntimeException('Status aktif (is_active) harus dikirim', 422);
        }

        $isActive = $this->normalizeBoolean($data['is_active']);
        $user = $this->repository->getUserById($targetUserId);

        if (!$user) {
            throw new RuntimeException('User tidak ditemukan', 404);
        }

        if ($user['role'] === 'user') {
            throw new RuntimeException('Status aktif (is_active) hanya bisa diubah untuk role selain user', 422);
        }

        if ($currentUserId === $targetUserId && !$isActive) {
            throw new RuntimeException('Akun sendiri tidak bisa dinonaktifkan', 422);
        }

        $this->repository->updateStatus($targetUserId, $isActive);
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value) && \in_array($value, [0, 1], true)) {
            return (bool) $value;
        }

        if (\is_string($value)) {
            $value = strtolower(trim($value));

            if (\in_array($value, ['true', '1'], true)) {
                return true;
            }

            if (\in_array($value, ['false', '0'], true)) {
                return false;
            }
        }

        throw new RuntimeException('Status aktif (is_active) harus bernilai boolean', 422);
    }
}
