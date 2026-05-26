<?php

declare(strict_types=1);

namespace App\Features\User\Status;

use App\Interfaces\RoutesInterface;

final class UpdateUserStatusRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'users'
            && is_numeric($segments[1])
            && $segments[2] === 'status'
            && !isset($segments[3])
            && $method === 'PUT'
        ) {
            $controller = new UpdateUserStatusController();
            $controller->update((int) $segments[1]);
            return true;
        }

        return false;
    }
}
