<?php

declare(strict_types=1);

namespace App\Features\Auth\Login;

final class LoginRoutes
{
    public static function dispatch(
        string $path,
        string $method
    ): bool {
        if ($path === '/auth/login' && $method == 'POST') {
            $controller = new LoginController();
            $controller->login();
            return true;
        }

        return false;
    }
}