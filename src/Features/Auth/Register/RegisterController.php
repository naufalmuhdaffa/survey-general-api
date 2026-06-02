<?php

declare(strict_types=1);

namespace App\Features\Auth\Register;

use App\Helpers\Response;
use App\Services\CookieService;
use RuntimeException;

final class RegisterController
{
    private RegisterService $service;

    public function __construct()
    {
        $this->service = new RegisterService();
    }

    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid'
            ], 400);
        }

        try {
            $token = $this->service->register(\is_array($data) ? $data : []);
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

        CookieService::setToken($token);

        Response::json([
            'status' => 'success',
            'message' => 'Registrasi berhasil'
        ], 201);
    }

    public function verifyNik(string $nik): void
    {
        try {
            $identity = $this->service->verifyNik($nik);
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

        Response::json([
            'status' => 'success',
            'data' => [
                'name' => $identity['name'],
                'address' => $identity['address'],
                'position' => $identity['position'],
            ]
        ], 200);
    }
}
