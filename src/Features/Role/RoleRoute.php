<?php

declare(strict_types=1);

namespace App\Features\Role;

use App\Features\Role\Create\CreateRoleRoute;
use App\Features\Role\Delete\DeleteRoleRoute;
use App\Features\Role\List\ListRoleRoute;
use App\Features\Role\Privilege\PrivilegeRoute;
use App\Features\Role\Update\UpdateRoleRoute;
use App\Interfaces\RouteInterface;

final class RoleRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (PrivilegeRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (ListRoleRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (CreateRoleRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateRoleRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (DeleteRoleRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
