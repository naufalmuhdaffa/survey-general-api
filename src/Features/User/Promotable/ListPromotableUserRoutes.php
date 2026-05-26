<?php

declare(strict_types=1);

namespace App\Features\User\Promotable;

use App\Interfaces\RoutesInterface;

final class ListPromotableUserRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/users/promotable' && $method === 'GET') {
            $controller = new ListPromotableUserController();
            $controller->list();
            return true;
        }

        return false;
    }
}
