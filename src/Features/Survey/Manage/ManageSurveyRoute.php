<?php

declare(strict_types=1);

namespace App\Features\Survey\Manage;

use App\Interfaces\RouteInterface;

final class ManageSurveyRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/surveys/manage' && $method === 'GET') {
            $controller = new ManageSurveyController();
            $controller->list();
            return true;
        }

        return false;
    }
}
