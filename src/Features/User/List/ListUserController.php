<?php

declare(strict_types=1);

namespace App\Features\User\List;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class ListUserController
{
    private ListUserService $service;

    public function __construct()
    {
        $this->service = new ListUserService();
    }

    public function list(): void
    {
        AuthMiddleware::handle('superadmin');

        Response::json([
            'status' => 'success',
            'data' => $this->service->listManagementUsers()
        ], 200);
    }
}
