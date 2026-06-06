<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\Update;

use App\Interfaces\RouteInterface;

final class UpdateRolePrivilegeRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'roles'
            && is_numeric($segments[1])
            && $segments[2] === 'privileges'
            && !isset($segments[3])
            && $method === 'PUT'
        ) {
            $controller = new UpdateRolePrivilegeController();
            $controller->update((int) $segments[1]);
            return true;
        }

        return false;
    }
}
