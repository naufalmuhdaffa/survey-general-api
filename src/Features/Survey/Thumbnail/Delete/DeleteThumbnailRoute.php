<?php

declare(strict_types=1);

namespace App\Features\Survey\Thumbnail\Delete;

use App\Interfaces\RouteInterface;

final class DeleteThumbnailRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'surveys'
            && is_numeric($segments[1])
            && $segments[2] === 'thumbnail'
            && !isset($segments[3])
            && $method === 'DELETE'
        ) {
            $controller = new DeleteThumbnailController();
            $controller->delete((int) $segments[1]);
            return true;
        }

        return false;
    }
}
