<?php

declare(strict_types=1);

namespace App\Features\Survey\Response;

use App\Features\Survey\Response\Create\CreateResponseRoutes;
use App\Interfaces\RoutesInterface;

final class ResponseRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (CreateResponseRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
