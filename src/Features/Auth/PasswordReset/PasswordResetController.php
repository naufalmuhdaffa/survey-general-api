<?php

declare(strict_types=1);

namespace App\Features\Auth\PasswordReset;

use App\Helpers\Response;
use RuntimeException;

final class PasswordResetController
{
    private PasswordResetService $service;

    public function __construct()
    {
        $this->service = new PasswordResetService();
    }

    public function requestReset(): void
    {
        $data = $this->jsonBody();

        try {
            $this->service->requestReset($data);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Email pemulihan terkirim, periksa kotak masuk Anda'
        ], 200);
    }

    public function resetPassword(): void
    {
        $data = $this->jsonBody();

        try {
            $this->service->resetPassword($data);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Kata sandi berhasil diperbarui'
        ], 200);
    }

    private function jsonBody(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid'
            ], 400);
        }

        return \is_array($data) ? $data : [];
    }

    private function handleRuntimeException(RuntimeException $e): void
    {
        $statusCode = $e->getCode();

        if (!\is_int($statusCode) || $statusCode < 400 || $statusCode > 599) {
            throw $e;
        }

        Response::json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], $statusCode);
    }
}
