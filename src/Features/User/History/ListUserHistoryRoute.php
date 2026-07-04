<?php

declare(strict_types=1);

namespace App\Features\User\History;

use App\Interfaces\RouteInterface;

final class ListUserHistoryRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/users/history' && $method === 'GET') {
            $controller = new ListUserHistoryController();
            $controller->list();
            return true;
        }

        return false;
    }
}
