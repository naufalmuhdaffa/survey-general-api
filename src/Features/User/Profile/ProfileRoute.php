<?php

declare(strict_types=1);

namespace App\Features\User\Profile;

use App\Interfaces\RouteInterface;

final class ProfileRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if ($path === '/users/profile' && $method === 'GET') {
            $controller = new ProfileController();
            $controller->show();
            return true;
        }

        if ($path === '/users/profile' && $method === 'PATCH') {
            $controller = new ProfileController();
            $controller->update();
            return true;
        }

        if ($path === '/users/profile/password' && $method === 'POST') {
            $controller = new ProfileController();
            $controller->changePassword();
            return true;
        }

        if ($path === '/users/profile/email/code' && $method === 'POST') {
            $controller = new ProfileController();
            $controller->sendEmailCode();
            return true;
        }

        if ($path === '/users/profile/email/verify' && $method === 'POST') {
            $controller = new ProfileController();
            $controller->verifyEmailCode();
            return true;
        }

        if ($path === '/users/profile/phone/otp' && $method === 'POST') {
            $controller = new ProfileController();
            $controller->sendPhoneOtp();
            return true;
        }

        if ($path === '/users/profile/phone/verify' && $method === 'POST') {
            $controller = new ProfileController();
            $controller->verifyPhoneOtp();
            return true;
        }

        return false;
    }
}
