<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail;

use App\Features\Survey\Thumbnail\Delete\DeleteThumbnailRoute;
use App\Features\Survey\Thumbnail\Update\UpdateThumbnailRoute;
use App\Interfaces\RouteInterface;

final class ThumbnailRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (UpdateThumbnailRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (DeleteThumbnailRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
