<?php

declare(strict_types=1);

namespace App\Features\User;

use App\Features\User\List\ListUserRoutes;
use App\Features\User\Permission\UserPermissionRoutes;
use App\Features\User\Promotable\ListPromotableUserRoutes;
use App\Features\User\Promote\PromoteUserRoutes;
use App\Features\User\Status\UpdateUserStatusRoutes;
use App\Interfaces\RoutesInterface;

final class UserRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (ListUserRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (ListPromotableUserRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (PromoteUserRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateUserStatusRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UserPermissionRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
