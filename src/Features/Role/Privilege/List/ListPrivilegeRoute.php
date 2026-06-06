<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\List;

use App\Interfaces\RouteInterface;

final class ListPrivilegeRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/privileges' && $method === 'GET') {
            $controller = new ListPrivilegeController();
            $controller->list();
            return true;
        }

        return false;
    }
}
