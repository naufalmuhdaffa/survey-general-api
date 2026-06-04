<?php

declare(strict_types=1);

namespace App\Features\Role\Create;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class CreateRoleController
{
    private CreateRoleService $service;

    public function __construct()
    {
        $this->service = new CreateRoleService();
    }

    public function create(): void
    {
        PrivilegeService::require('role:create');
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid',
            ], 400);
        }

        try {
            $roleId = $this->service->create(\is_array($data) ? $data : []);
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
            'message' => 'Role berhasil dibuat',
            'data' => ['id' => $roleId],
        ], 201);
    }
}
