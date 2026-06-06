<?php

declare(strict_types=1);

namespace App\Features\User\Promotable;

use App\Helpers\Response;
use App\Services\PrivilegeService;

final class ListPromotableUserController
{
    private ListPromotableUserService $service;

    public function __construct()
    {
        $this->service = new ListPromotableUserService();
    }

    public function list(): void
    {
        PrivilegeService::require('user:read');

        Response::json([
            'status' => 'success',
            'data' => $this->service->listPromotableUsers()
        ], 200);
    }
}
