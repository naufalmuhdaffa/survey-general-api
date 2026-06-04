<?php

declare(strict_types=1);

namespace App\Features\Role\Create;

use App\Interfaces\RouteInterface;

final class CreateRoleRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/roles' && $method === 'POST') {
            $controller = new CreateRoleController();
            $controller->create();
            return true;
        }

        return false;
    }
}
