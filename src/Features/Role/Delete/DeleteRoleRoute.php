<?php

declare(strict_types=1);

namespace App\Features\Role\Delete;

use App\Interfaces\RouteInterface;

final class DeleteRoleRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1])
            && $segments[0] === 'roles'
            && is_numeric($segments[1])
            && !isset($segments[2])
            && $method === 'DELETE'
        ) {
            $controller = new DeleteRoleController();
            $controller->delete((int) $segments[1]);
            return true;
        }

        return false;
    }
}
