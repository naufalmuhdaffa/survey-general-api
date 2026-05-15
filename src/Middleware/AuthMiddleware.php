<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;
use App\Services\JwtService;

final class AuthMiddleware
{
    public static function handle(string ...$roles): void
    {
        $token = JwtService::bearerToken();

        if ($token === null) {
            Response::json([
                'status' => 'error',
                'message' => 'Token tidak ditemukan'
            ], 401);
        }

        $payload = JwtService::verify($token);

        if ($payload === null) {
            Response::json([
                'status' => 'error',
                'message' => 'Token tidak valid atau sudah kedaluwarsa'
            ], 401);
        }

        if (!empty($roles) && !\in_array($payload->data->role, $roles)) {
            Response::json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses'
            ], 403);
        }
    }
}