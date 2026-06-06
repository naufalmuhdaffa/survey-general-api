<?php

declare(strict_types=1);

namespace App;

use App\Features\Auth\AuthRoute;
use App\Features\Role\RoleRoute;
use App\Features\Survey\SurveyRoute;
use App\Features\User\UserRoute;
use App\Interfaces\RouteInterface;

final class Route implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (AuthRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (SurveyRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (RoleRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UserRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
