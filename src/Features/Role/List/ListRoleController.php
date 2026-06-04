<?php

declare(strict_types=1);

namespace App\Features\Role\List;

use App\Helpers\Response;
use App\Services\PrivilegeService;

final class ListRoleController
{
    private ListRoleService $service;

    public function __construct()
    {
        $this->service = new ListRoleService();
    }

    public function list(): void
    {
        PrivilegeService::require('role:read');

        Response::json([
            'status' => 'success',
            'data' => $this->service->list(),
        ], 200);
    }
}
