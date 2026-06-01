<?php

declare(strict_types=1);

namespace App\Features\Survey\Page;

use App\Features\Survey\Page\Upsert\UpsertPageRoute;
use App\Interfaces\RouteInterface;

final class PageRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (UpsertPageRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
