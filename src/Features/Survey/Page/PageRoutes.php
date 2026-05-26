<?php

declare(strict_types=1);

namespace App\Features\Survey\Page;

use App\Features\Survey\Page\Upsert\UpsertPageRoutes;
use App\Interfaces\RoutesInterface;

final class PageRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (UpsertPageRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
