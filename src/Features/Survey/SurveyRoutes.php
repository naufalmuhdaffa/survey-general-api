<?php

declare(strict_types=1);

namespace App\Features\Survey;

use App\Features\Survey\Create\CreateSurveyRoutes;
use App\Interfaces\RoutesInterface;

final class SurveyRoutes implements RoutesInterface
{
    public static function dispatch(
        string $path,
        string $method,
        array $segments
    ): bool {
        if (CreateSurveyRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}