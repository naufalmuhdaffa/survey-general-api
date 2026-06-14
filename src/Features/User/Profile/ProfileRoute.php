<?php

declare(strict_types=1);

namespace App\Features\User\Profile;

use App\Interfaces\RouteInterface;

final class ProfileRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/users/profile' && $method === 'GET') {
            $controller = new ProfileController();
            $controller->show();
            return true;
        }

        if ($path === '/users/profile' && $method === 'PATCH') {
            $controller = new ProfileController();
            $controller->update();
            return true;
        }

        if ($path === '/users/profile/password' && $method === 'POST') {
            $controller = new ProfileController();
            $controller->changePassword();
            return true;
        }

        return false;
    }
}
