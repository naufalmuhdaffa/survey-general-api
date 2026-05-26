<?php

declare(strict_types=1);

namespace App\Features\User\List;

use App\Interfaces\RoutesInterface;

final class ListUserRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/users' && $method === 'GET') {
            $controller = new ListUserController();
            $controller->list();
            return true;
        }

        return false;
    }
}
