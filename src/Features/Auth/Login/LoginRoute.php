<?php

declare(strict_types=1);

namespace App\Features\Auth\Login;

use App\Interfaces\RouteInterface;

final class LoginRoute implements RouteInterface
{
    public static function dispatch(
        string $path,
        string $method,
        array $segments
    ): bool {
        if ($path === '/auth/login' && $method == 'POST') {
            $controller = new LoginController();
            $controller->login();
            return true;
        }

        return false;
    }
}