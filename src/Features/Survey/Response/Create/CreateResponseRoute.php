<?php

declare(strict_types=1);

namespace App\Features\Survey\Response\Create;

use App\Interfaces\RouteInterface;

final class CreateResponseRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'surveys'
            && is_numeric($segments[1])
            && $segments[2] === 'responses'
            && !isset($segments[3])
        ) {
            $controller = new CreateResponseController();

            if ($method === 'GET') {
                $controller->detail((int) $segments[1]);
                return true;
            }

            if ($method === 'PUT') {
                $controller->saveDraft((int) $segments[1]);
                return true;
            }

            if ($method === 'POST') {
                $controller->create((int) $segments[1]);
                return true;
            }
        }

        return false;
    }
}
