<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option;

use App\Interfaces\RouteInterface;
use App\Features\Survey\Question\Option\Create\CreateOptionRoute;
use App\Features\Survey\Question\Option\Update\UpdateOptionRoute;
use App\Features\Survey\Question\Option\Delete\DeleteOptionRoute;

final class OptionRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (CreateOptionRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateOptionRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (DeleteOptionRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}