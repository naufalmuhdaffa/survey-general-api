<?php

declare(strict_types=1);

namespace App\Features\Auth\Logout;

use App\Interfaces\RoutesInterface;

final class LogoutRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/auth/logout' && $method === 'POST') {
            $controller = new LogoutController();
            $controller->logout();
            return true;
        }

        return false;
    }
}
