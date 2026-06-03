<?php

declare(strict_types=1);

namespace App\Features\Survey\Form;

use App\Interfaces\RouteInterface;

final class FormSurveyRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'surveys'
            && is_numeric($segments[1])
            && $segments[2] === 'form'
            && !isset($segments[3])
            && $method === 'GET'
        ) {
            $controller = new FormSurveyController();
            $controller->form((int) $segments[1]);
            return true;
        }

        return false;
    }
}
