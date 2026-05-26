<?php

declare(strict_types=1);

namespace App\Features\User\Promotable;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class ListPromotableUserController
{
    private ListPromotableUserRepository $repository;

    public function __construct()
    {
        $this->repository = new ListPromotableUserRepository();
    }

    public function list(): void
    {
        AuthMiddleware::handle('superadmin');

        $users = $this->repository->getPromotableUsers();
        $formattedUsers = [];

        foreach ($users as $user) {
            $formattedUsers[] = $this->formatUser($user);
        }

        Response::json([
            'status' => 'success',
            'data' => $formattedUsers
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
