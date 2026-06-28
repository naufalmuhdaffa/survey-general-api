<?php

declare(strict_types=1);

namespace App\Features\User\Role;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class UpdateUserRoleController
{
    private UpdateUserRoleService $service;

    public function __construct()
    {
        $this->service = new UpdateUserRoleService();
    }

    public function update(int $userId): void
    {
        $currentUser = PrivilegeService::require('user:update');

        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid'
            ], 400);
        }

        try {
            $this->service->update(
                (int) $currentUser['id'],
                $userId,
                \is_array($data) ? $data : []
            );
        } catch (RuntimeException $e) {
            $statusCode = $e->getCode();

            if ($statusCode < 400 || $statusCode > 599) {
                throw $e;
            }

            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $statusCode);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Role user berhasil diperbarui'
        ], 200);
    }
}
