<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail;

use App\Features\Survey\Thumbnail\Delete\DeleteThumbnailRoutes;
use App\Features\Survey\Thumbnail\Update\UpdateThumbnailRoutes;
use App\Interfaces\RoutesInterface;

final class ThumbnailRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (UpdateThumbnailRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (DeleteThumbnailRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
