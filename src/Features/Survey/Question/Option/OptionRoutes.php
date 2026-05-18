<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option;

use App\Interfaces\RoutesInterface;
use App\Features\Survey\Question\Option\Create\CreateOptionRoutes;
use App\Features\Survey\Question\Option\Update\UpdateOptionRoutes;

final class OptionRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (CreateOptionRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateOptionRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}