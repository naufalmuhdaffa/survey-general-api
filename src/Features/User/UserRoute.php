<?php

declare(strict_types=1);

namespace App\Features\User;

use App\Features\User\List\ListUserRoute;
use App\Features\User\Profile\ProfileRoute;
use App\Features\User\Promotable\ListPromotableUserRoute;
use App\Features\User\Role\UpdateUserRoleRoute;
use App\Features\User\Status\UpdateUserStatusRoute;
use App\Interfaces\RouteInterface;

final class UserRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (ProfileRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (ListUserRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (ListPromotableUserRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateUserRoleRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateUserStatusRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
