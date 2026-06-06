<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\Detail;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class DetailRolePrivilegeController
{
    private DetailRolePrivilegeService $service;

    public function __construct()
    {
        $this->service = new DetailRolePrivilegeService();
    }

    public function detail(int $roleId): void
    {
        PrivilegeService::require('role:read');

        try {
            $data = $this->service->detail($roleId);
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
            'data' => $data,
        ], 200);
    }
}
