<?php

declare(strict_types=1);

namespace App\Features\Auth;

use App\Features\Auth\Register\RegisterRoutes;
use App\Features\Auth\Login\LoginRoutes;

final class AuthRoutes
{
    public static function dispatch(string $method, string $path, array $segments): bool
    {
        if (RegisterRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (LoginRoutes::dispatch($path, $method)) {
            return true;
        }

        return false;
    }
}