<?php

declare(strict_types=1);

namespace App\Features\Auth;

use App\Features\Auth\Register\RegisterRoutes;
use App\Features\Auth\Login\LoginRoutes;
use App\Interfaces\RoutesInterface;

final class AuthRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (RegisterRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (LoginRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}