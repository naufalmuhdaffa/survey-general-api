<?php

declare(strict_types=1);

namespace App\Features\Auth\Register;

use App\Interfaces\RouteInterface;

final class RegisterRoute implements RouteInterface
{
    public static function dispatch(
        string $path,
        string $method,
        array $segments
    ): bool {
        if (
            isset($segments[0], $segments[1], $segments[2])
            && $segments[0] === 'auth'
            && $segments[1] === 'verify'
            && $method === 'GET'
        ) {
            $controller = new RegisterController();
            $controller->verifyNik($segments[2]);
            return true;
        }

        if ($path === '/auth/register' && $method === 'POST') {
            $controller = new RegisterController();
            $controller->register();
            return true;
        }

        if ($path === '/auth/register/email/code' && $method === 'POST') {
            $controller = new RegisterController();
            $controller->sendEmailCode();
            return true;
        }

        if ($path === '/auth/register/email/verify' && $method === 'POST') {
            $controller = new RegisterController();
            $controller->verifyEmailCode();
            return true;
        }

        if ($path === '/auth/register/phone/otp' && $method === 'POST') {
            $controller = new RegisterController();
            $controller->sendPhoneOtp();
            return true;
        }

        if ($path === '/auth/register/phone/verify' && $method === 'POST') {
            $controller = new RegisterController();
            $controller->verifyPhoneOtp();
            return true;
        }

        return false;
    }
}
