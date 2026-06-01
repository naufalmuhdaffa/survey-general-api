<?php

declare(strict_types=1);

namespace App\Features\User\Permission;

use App\Interfaces\RouteInterface;

final class UserPermissionRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        $controller = new UserPermissionController();

        if ($path === '/permissions' && $method === 'GET') {
            $controller->listPermissions();
            return true;
        }

        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'users'
            && is_numeric($segments[1])
            && $segments[2] === 'permissions'
            && !isset($segments[3])
        ) {
            if ($method === 'GET') {
                $controller->getUserPermissions((int) $segments[1]);
                return true;
            }

            if ($method === 'PUT') {
                $controller->updateUserPermissions((int) $segments[1]);
                return true;
            }
        }

        return false;
    }
}
