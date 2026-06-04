<?php

declare(strict_types=1);

namespace App\Features\Role\List;

use App\Interfaces\RouteInterface;

final class ListRoleRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/roles' && $method === 'GET') {
            $controller = new ListRoleController();
            $controller->list();
            return true;
        }

        return false;
    }
}
