<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Create;

use App\Interfaces\RoutesInterface;

final class CreateOptionRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1], $segments[2], $segments[3], $segments[4])
            && $segments[0] === 'surveys'
            && is_numeric($segments[1])
            && $segments[2] === 'questions'
            && is_numeric($segments[3])
            && $segments[4] === 'options'
            && $method === 'POST'
        ) {
            $controller = new CreateOptionController();
            $controller->create((int) $segments[1], (int) $segments[3]);
            return true;
        }

        return false;
    }
}