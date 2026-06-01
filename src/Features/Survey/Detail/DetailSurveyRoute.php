<?php

declare(strict_types=1);

namespace App\Features\Survey\Detail;

use App\Interfaces\RouteInterface;

final class DetailSurveyRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1])
            && $segments[0] === 'surveys'
            && is_numeric($segments[1])
            && !isset($segments[2])
            && $method === 'GET'
        ) {
            $controller = new DetailSurveyController();
            $controller->detail((int) $segments[1]);
            return true;
        }

        return false;
    }
}