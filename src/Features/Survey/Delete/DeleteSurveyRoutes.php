<?php

declare(strict_types=1);

namespace App\Features\Survey\Delete;

use App\Interfaces\RoutesInterface;

final class DeleteSurveyRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1])
            && $segments[0] === 'surveys'
            && is_numeric($segments[1])
            && !isset($segments[2])
            && $method === 'DELETE'
        ) {
            $controller = new DeleteSurveyController();
            $controller->delete((int) $segments[1]);
            return true;
        }

        return false;
    }
}