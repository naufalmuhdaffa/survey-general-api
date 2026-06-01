<?php

declare(strict_types=1);

namespace App\Features\User\Promotable;

use App\Interfaces\RouteInterface;

final class ListPromotableUserRoute implements RouteInterface
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
