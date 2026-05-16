<?php

declare(strict_types=1);

namespace App;

use App\Features\Auth\AuthRoutes;
use App\Features\Survey\SurveyRoutes;
use App\Interfaces\RoutesInterface;

final class Routes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (AuthRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (SurveyRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}