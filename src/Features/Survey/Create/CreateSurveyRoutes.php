<?php

declare(strict_types=1);

namespace App\Features\Survey\Create;

use App\Interfaces\RoutesInterface;

final class CreateSurveyRoutes implements RoutesInterface
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