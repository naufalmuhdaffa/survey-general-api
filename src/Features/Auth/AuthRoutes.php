<?php

declare(strict_types=1);

namespace App\Features\Auth;

use App\Features\Auth\Register\RegisterRoutes;

final class AuthRoutes
{
    public static function dispatch(string $method, string $path, array $segments): bool
    {
        if (RegisterRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}