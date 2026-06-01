<?php

declare(strict_types=1);

namespace App\Features\Auth\Logout;

use App\Services\CookieService;

final class LogoutService
{
    public function logout(): void
    {
        CookieService::clearToken();
    }
}
