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
        $formattedUsers = [];

        foreach ($users as $user) {
            $formattedUsers[] = $this->formatUser($user);
        }

        Response::json([
            'status' => 'success',
            'data' => $formattedUsers
        ], 200);
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
