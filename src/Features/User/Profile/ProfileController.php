<?php

declare(strict_types=1);

namespace App\Features\User\Profile;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use RuntimeException;

final class ProfileController
{
    private ProfileService $service;

    public function __construct()
    {
        $this->service = new ProfileService();
    }

    public function show(): void
    {
        $authUser = AuthMiddleware::handle();

        try {
            $profile = $this->service->profile((int) $authUser['id']);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'data' => $profile,
        ], 200);
    }

    public function update(): void
    {
        $authUser = AuthMiddleware::handle();
        $data = $this->jsonBody();

        try {
            $profile = $this->service->updateProfile((int) $authUser['id'], $data);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => $profile,
        ], 200);
    }

    public function changePassword(): void
    {
        $authUser = AuthMiddleware::handle();
        $data = $this->jsonBody();

        try {
            $this->service->changePassword((int) $authUser['id'], $data);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Kata sandi berhasil diperbarui',
        ], 200);
    }

    public function sendEmailCode(): void
    {
        $authUser = AuthMiddleware::handle();

        try {
            $this->service->sendEmailCode((int) $authUser['id']);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Kode verifikasi email berhasil dikirim',
        ], 200);
    }

    public function verifyEmailCode(): void
    {
        $authUser = AuthMiddleware::handle();
        $data = $this->jsonBody();

        try {
            $profile = $this->service->verifyEmailCode((int) $authUser['id'], $data);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Email berhasil diverifikasi',
            'data' => $profile,
        ], 200);
    }

    public function sendPhoneOtp(): void
    {
        $authUser = AuthMiddleware::handle();

        try {
            $this->service->sendPhoneOtp((int) $authUser['id']);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'OTP nomor telepon berhasil dikirim',
        ], 200);
    }

    public function verifyPhoneOtp(): void
    {
        $authUser = AuthMiddleware::handle();
        $data = $this->jsonBody();

        try {
            $profile = $this->service->verifyPhoneOtp((int) $authUser['id'], $data);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Nomor telepon berhasil diverifikasi',
            'data' => $profile,
        ], 200);
    }

    private function jsonBody(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'status' => 'error',
                'message' => 'Format JSON tidak valid',
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
            'message' => $e->getMessage(),
        ], $statusCode);
    }
}
