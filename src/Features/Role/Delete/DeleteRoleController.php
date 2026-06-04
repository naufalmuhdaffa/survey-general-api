<?php

declare(strict_types=1);

namespace App\Features\Role\Delete;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class DeleteRoleController
{
    private DeleteRoleService $service;

    public function __construct()
    {
        $this->service = new DeleteRoleService();
    }

    public function delete(int $roleId): void
    {
        PrivilegeService::require('role:delete');

        try {
            $this->service->delete($roleId);
        } catch (RuntimeException $e) {
            $statusCode = $e->getCode();

            if ($statusCode < 400 || $statusCode > 599) {
                throw $e;
            }

            Response::json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $statusCode);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Role berhasil dihapus',
        ], 200);
    }
}
