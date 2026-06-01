<?php

declare(strict_types=1);

namespace App\Features\Survey\List;

use App\Interfaces\RouteInterface;

final class ListSurveyRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/surveys' && $method === 'GET') {
            $controller = new ListSurveyController();
            $controller->list();
            return true;
        }

        return false;
    }
}