<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\List;

use App\Helpers\Response;
use App\Services\PrivilegeService;

final class ListPrivilegeController
{
    private ListPrivilegeService $service;

    public function __construct()
    {
        $this->service = new ListPrivilegeService();
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
