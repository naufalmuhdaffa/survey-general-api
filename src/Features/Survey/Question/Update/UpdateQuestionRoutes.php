<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Update;

use App\Interfaces\RoutesInterface;

final class UpdateQuestionRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1], $segments[2], $segments[3])
            && $segments[0] === 'surveys'
            && is_numeric($segments[1])
            && $segments[2] === 'questions'
            && is_numeric($segments[3])
            && !isset($segments[4])
            && $method === 'PUT'
        ) {
            (new UpdateQuestionController())->update((int) $segments[1], (int) $segments[3]);
            return true;
        }

        return false;
    }
}