<?php

declare(strict_types=1);

namespace App\Features\Auth;

use App\Features\Auth\Register\RegisterRoute;
use App\Features\Auth\Login\LoginRoute;
use App\Features\Auth\Logout\LogoutRoute;
use App\Interfaces\RouteInterface;

final class AuthRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (RegisterRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (LoginRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (LogoutRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
