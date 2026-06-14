<?php

declare(strict_types=1);

namespace App\Features\Auth\Register;

use App\Helpers\Response;
use App\Services\CookieService;
use RuntimeException;

final class RegisterController
{
    private RegisterService $service;
    private RegisterVerificationService $verificationService;

    public function __construct()
    {
        $this->service = new RegisterService();
        $this->verificationService = new RegisterVerificationService();
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

            if (!\is_int($statusCode) || $statusCode < 400 || $statusCode > 599) {
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
            'message' => 'Registrasi berhasil',
            'data' => [
                'token' => $token,
            ],
            'token' => $token,
        ], 201);
    }

    public function verifyNik(string $nik): void
    {
        try {
            $identity = $this->service->verifyNik($nik);
        } catch (RuntimeException $e) {
            $statusCode = $e->getCode();

            if (!\is_int($statusCode) || $statusCode < 400 || $statusCode > 599) {
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

    public function sendEmailCode(): void
    {
        $data = $this->jsonBody();

        try {
            $this->verificationService->sendEmailCode($data);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Kode verifikasi email berhasil dikirim'
        ], 200);
    }

    public function verifyEmailCode(): void
    {
        $data = $this->jsonBody();

        try {
            $this->verificationService->verifyEmailCode($data);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Email berhasil diverifikasi'
        ], 200);
    }

    public function sendPhoneOtp(): void
    {
        $data = $this->jsonBody();

        try {
            $this->verificationService->sendPhoneOtp($data);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'OTP nomor telepon berhasil dikirim'
        ], 200);
    }

    public function verifyPhoneOtp(): void
    {
        $data = $this->jsonBody();

        try {
            $this->verificationService->verifyPhoneOtp($data);
        } catch (RuntimeException $e) {
            $this->handleRuntimeException($e);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Nomor telepon berhasil diverifikasi'
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
