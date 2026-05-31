<?php

declare(strict_types=1);

namespace App\Features\Auth\Logout;

use App\Helpers\Response;
use App\Services\CookieService;

final class LogoutController
{
    public function logout(): void
    {
        CookieService::clearToken();

        Response::json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }
}
