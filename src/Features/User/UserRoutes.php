<?php

declare(strict_types=1);

namespace App\Features\User;

use App\Features\User\List\ListUserRoutes;
use App\Interfaces\RoutesInterface;

final class UserRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (ListUserRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
