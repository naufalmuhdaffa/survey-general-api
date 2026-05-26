<?php

declare(strict_types=1);

namespace App\Features\User\Promote;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class PromoteUserController
{
    private PromoteUserRepository $repository;

    public function __construct()
    {
        $this->repository = new PromoteUserRepository();
    }

    public function promote(int $userId): void
    {
        AuthMiddleware::handle('superadmin');

        $data = json_decode(file_get_contents('php://input'), true);
        $role = trim((string) ($data['role'] ?? ''));

        if ($role !== 'admin_opd') {
            Response::json([
                'status' => 'error',
                'message' => 'Promote hanya boleh ke role admin_opd'
            ], 422);
        }

        $user = $this->repository->getUserById($userId);

        if (!$user) {
            Response::json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        if ($user['role'] !== 'user') {
            Response::json([
                'status' => 'error',
                'message' => 'Hanya user biasa yang bisa dipromosikan'
            ], 422);
        }

        $this->repository->updateRole($userId, $role);

        Response::json([
            'status' => 'success',
            'message' => 'User berhasil dipromosikan'
        ]);
    }
}
