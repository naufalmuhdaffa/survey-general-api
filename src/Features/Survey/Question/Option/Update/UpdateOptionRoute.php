<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Update;

use App\Interfaces\RouteInterface;

final class UpdateOptionRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1], $segments[2], $segments[3], $segments[4], $segments[5])
            && $segments[0] === 'surveys'
            && is_numeric($segments[1])
            && $segments[2] === 'questions'
            && is_numeric($segments[3])
            && $segments[4] === 'options'
            && is_numeric($segments[5])
            && !isset($segments[6])
            && $method === 'PUT'
        ) {
            $controller = new UpdateOptionController();
            $controller->update((int) $segments[1], (int) $segments[3], (int) $segments[5]);
            return true;
        }

        return false;
    }
}