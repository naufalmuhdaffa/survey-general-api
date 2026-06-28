<?php

declare(strict_types=1);

namespace App\Features\Auth\Login;

use App\Helpers\Response;
use App\Services\CookieService;
use App\Services\CsrfService;
use RuntimeException;

final class LoginController
{
    private LoginService $service;

    public function __construct()
    {
        $this->service = new LoginService();
    }

    public function login(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid'
            ], 400);
        }

        try {
            $session = $this->service->login(\is_array($data) ? $data : []);
        } catch (RuntimeException $e) {
            $statusCode = $e->getCode();

            if ($statusCode < 400 || $statusCode > 599) {
                throw $e;
            }

            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $statusCode);
        }

        CookieService::setToken($session['token']);
        CsrfService::setToken($session['csrf_token']);

        Response::json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'authenticated' => true,
            ],
        ], 200);
    }
}
