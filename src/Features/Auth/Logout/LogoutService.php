<?php

declare(strict_types=1);

namespace App\Features\Auth\Logout;

use App\Repositories\AuthRepository;
use App\Services\CookieService;
use App\Services\JwtService;

final class LogoutService
{
    private AuthRepository $repository;

    public function __construct()
    {
        $this->repository = new AuthRepository();
    }

    public function logout(): void
    {
        $token = JwtService::token();

        if ($token !== null) {
            $payload = JwtService::verify($token);
            $expiresAt = (int) ($payload->exp ?? time());

            $this->repository->revokeToken(
                $token,
                date('Y-m-d H:i:s', $expiresAt)
            );
        }

        CookieService::clearToken();
    }
}
