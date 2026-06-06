<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\Update;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class UpdateRolePrivilegeController
{
    private UpdateRolePrivilegeService $service;

    public function __construct()
    {
        $this->service = new UpdateRolePrivilegeService();
    }

    public function update(int $roleId): void
    {
        PrivilegeService::require('role:update');
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid',
            ], 400);
        }

        try {
            $this->service->update($roleId, \is_array($data) ? $data : []);
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
            'message' => 'Privilege role berhasil diperbarui',
        ], 200);
    }
}
