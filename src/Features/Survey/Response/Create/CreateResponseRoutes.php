<?php

declare(strict_types=1);

namespace App\Features\Survey\Response\Create;

use App\Interfaces\RoutesInterface;

final class CreateResponseRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'surveys'
            && is_numeric($segments[1])
            && $segments[2] === 'responses'
            && !isset($segments[3])
            && $method === 'POST'
        ) {
            $controller = new CreateResponseController();
            $controller->create((int) $segments[1]);
            return true;
        }

        return false;
    }
}
