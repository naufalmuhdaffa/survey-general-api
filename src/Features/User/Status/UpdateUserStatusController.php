<?php

declare(strict_types=1);

namespace App\Features\User\Status;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class UpdateUserStatusController
{
    private UpdateUserStatusRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateUserStatusRepository();
    }

    public function update(int $userId): void
    {
        $currentUser = AuthMiddleware::handle('superadmin');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!\is_array($data) || !\array_key_exists('is_active', $data)) {
            Response::json([
                'status' => 'error',
                'message' => 'Status aktif (is_active) harus dikirim'
            ], 422);
        }

        $isActive = $this->normalizeBoolean($data['is_active']);
        $user = $this->repository->getUserById($userId);

        if (!$user) {
            Response::json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        if (!\in_array($user['role'], ['superadmin', 'admin_opd'], true)) {
            Response::json([
                'status' => 'error',
                'message' => 'Status hanya bisa diubah untuk user manajemen'
            ], 422);
        }

        if ((int) $currentUser['id'] === $userId && !$isActive) {
            Response::json([
                'status' => 'error',
                'message' => 'Akun sendiri tidak bisa dinonaktifkan'
            ], 422);
        }

        if ($user['role'] === 'superadmin' && !$isActive && $this->repository->countActiveSuperadmins() <= 1) {
            Response::json([
                'status' => 'error',
                'message' => 'Minimal harus ada satu superadmin aktif. Superadmin terakhir tidak dapat dinonaktifkan'
            ], 422);
        }

        $this->repository->updateStatus($userId, $isActive);

        Response::json([
            'status' => 'success',
            'message' => 'Status user berhasil diperbarui'
        ]);
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

        Response::json([
            'status' => 'error',
            'message' => 'Status aktif (is_active) harus bernilai boolean'
        ], 422);
    }
}
