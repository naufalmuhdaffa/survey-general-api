<?php

declare(strict_types=1);

namespace App\Features\Survey\Analysis;

use App\Interfaces\RouteInterface;

final class AnalysisSurveyRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/surveys/analytics' && $method === 'GET') {
            $controller = new AnalysisSurveyController();
            $controller->list();
            return true;
        }

        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'surveys'
            && is_numeric($segments[1])
            && $segments[2] === 'analytics'
        ) {
            $controller = new AnalysisSurveyController();

            if ($method === 'GET' && !isset($segments[3])) {
                $controller->detail((int) $segments[1]);
                return true;
            }

            if ($method === 'GET' && ($segments[3] ?? '') === 'export') {
                $controller->export((int) $segments[1]);
                return true;
            }
        }

        return false;
    }
}
