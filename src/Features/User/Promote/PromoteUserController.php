<?php

declare(strict_types=1);

namespace App\Features\User\Promote;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use RuntimeException;

final class PromoteUserController
{
    private PromoteUserService $service;

    public function __construct()
    {
        $this->service = new PromoteUserService();
    }

    public function promote(int $userId): void
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
            $this->service->promote($userId, \is_array($data) ? $data : []);
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
            'message' => 'User berhasil dipromosikan'
        ], 200);
    }
}
