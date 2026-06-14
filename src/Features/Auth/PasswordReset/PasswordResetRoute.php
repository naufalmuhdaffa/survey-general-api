<?php

declare(strict_types=1);

namespace App\Features\Auth\PasswordReset;

use App\Interfaces\RouteInterface;

final class PasswordResetRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/auth/forgot-password' && $method === 'POST') {
            $controller = new PasswordResetController();
            $controller->requestReset();
            return true;
        }

        if ($path === '/auth/reset-password' && $method === 'POST') {
            $controller = new PasswordResetController();
            $controller->resetPassword();
            return true;
        }

        return false;
    }
}
