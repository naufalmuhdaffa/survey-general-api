<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege;

use App\Features\Role\Privilege\Detail\DetailRolePrivilegeRoute;
use App\Features\Role\Privilege\List\ListPrivilegeRoute;
use App\Features\Role\Privilege\Update\UpdateRolePrivilegeRoute;
use App\Interfaces\RouteInterface;

final class PrivilegeRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (ListPrivilegeRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (DetailRolePrivilegeRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateRolePrivilegeRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
