<?php

declare(strict_types=1);

namespace App\Features\User\Permission;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use RuntimeException;

final class UserPermissionController
{
    private UserPermissionService $service;

    public function __construct()
    {
        $this->service = new UserPermissionService();
    }

    public function listPermissions(): void
    {
        AuthMiddleware::handle('superadmin');

        Response::json([
            'status' => 'success',
            'data' => $this->service->listPermissions()
        ], 200);
    }

    public function getUserPermissions(int $userId): void
    {
        AuthMiddleware::handle('superadmin');

        try {
            $data = $this->service->getUserPermissions($userId);
        } catch (RuntimeException $e) {
            $this->errorResponse($e);
        }

        Response::json([
            'status' => 'success',
            'data' => $data
        ], 200);
    }

    public function updateUserPermissions(int $userId): void
    {
        AuthMiddleware::handle('superadmin');

        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid'
            ], 400);
        }

        try {
            $this->service->updateUserPermissions($userId, \is_array($data) ? $data : []);
        } catch (RuntimeException $e) {
            $this->errorResponse($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Privilege user berhasil diperbarui'
        ], 200);
    }

    private function errorResponse(RuntimeException $e): never
    {
        $statusCode = $e->getCode();

        if ($statusCode < 400 || $statusCode > 599) {
            throw $e;
        }

        Response::json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], $statusCode);
    }
}
