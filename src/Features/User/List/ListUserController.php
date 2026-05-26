<?php

declare(strict_types=1);

namespace App\Features\User\List;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class ListUserController
{
    private ListUserRepository $repository;

    public function __construct()
    {
        $this->repository = new ListUserRepository();
    }

    public function list(): void
    {
        AuthMiddleware::handle('superadmin');

        $users = $this->repository->getManagementUsers();

        Response::json([
            'status' => 'success',
            'data' => \array_map([$this, 'formatUser'], $users)
        ]);
    }

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
