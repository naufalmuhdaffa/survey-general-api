<?php

declare(strict_types=1);

namespace App\Features\Survey\Response;

use App\Features\Survey\Response\Create\CreateResponseRoute;
use App\Interfaces\RouteInterface;

final class ResponseRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (CreateResponseRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
