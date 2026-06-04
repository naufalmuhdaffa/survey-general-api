<?php

declare(strict_types=1);

namespace App\Features\User\Role;

use App\Interfaces\RouteInterface;

final class UpdateUserRoleRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'users'
            && is_numeric($segments[1])
            && $segments[2] === 'role'
            && !isset($segments[3])
            && $method === 'PUT'
        ) {
            $controller = new UpdateUserRoleController();
            $controller->update((int) $segments[1]);
            return true;
        }

        return false;
    }
}
