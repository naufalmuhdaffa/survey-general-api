<?php

declare(strict_types=1);

namespace App;

use App\Features\Auth\AuthRoutes;

final class Routes
{
    public static function dispatch(string $method, string $path, array $segments): bool
    {
        if (AuthRoutes::dispatch($method, $path, $segments)) {
            return true;
        }

        return false;
    }
}