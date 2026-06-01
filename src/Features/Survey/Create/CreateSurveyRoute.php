<?php

declare(strict_types=1);

namespace App\Features\Survey\Create;

use App\Interfaces\RouteInterface;

final class CreateSurveyRoute implements RouteInterface
{
    public static function dispatch(
        string $path,
        string $method,
        array $segments
    ): bool {
        if ($path === '/surveys' && $method === 'POST') {
            $controller = new CreateSurveyController();
            $controller->create();
            return true;
        }

        return false;
    }
}