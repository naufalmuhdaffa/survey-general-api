<?php

declare(strict_types=1);

namespace App\Features\Auth\Register;

use App\Interfaces\RoutesInterface;

final class RegisterRoutes implements RoutesInterface
{
    public static function dispatch(
        string $path,
        string $method,
        array $segments
    ): bool {
        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'auth'
            && $segments[1] === 'verify'
            && $method === 'GET'
        ) {
            $controller = new RegisterController();
            $controller->verifyNik($segments[2]);
            return true;
        }

        if ($path === '/auth/register' && $method === 'POST') {
            $controller = new RegisterController();
            $controller->register();
            return true;
        }

        return false;
    }
}