<?php

declare(strict_types=1);

namespace App\Features\Auth\Logout;

use App\Helpers\Response;

final class LogoutController
{
    private LogoutService $service;

    public function __construct()
    {
        $this->service = new LogoutService();
    }

    public function logout(): void
    {
        $this->service->logout();

        Response::json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ], 200);
    }
}
