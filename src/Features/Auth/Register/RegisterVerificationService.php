<?php

declare(strict_types=1);

namespace App\Features\Auth\Register;

use App\Services\MailService;
use App\Services\SmsService;
use RuntimeException;

final class RegisterVerificationService
{
    private const int CODE_COOLDOWN_SECONDS = 60;
    private const int CODE_EXPIRE_MINUTES = 10;
    private const int MAX_ATTEMPTS = 5;

    private RegisterVerificationRepository $repository;
    private MailService $mailService;
    private SmsService $smsService;

    public function __construct()
    {
        $this->repository = new RegisterVerificationRepository();
        $this->mailService = new MailService();
        $this->smsService = new SmsService();
    }

    public function sendEmailCode(array $data): void
    {
        $email = $this->normalizeEmail($data['email'] ?? null);
        $this->ensureCanSend('email', $email);

        $code = $this->generateCode();
        $this->mailService->send(
            $email,
            'Kode Verifikasi Email Survey PemKot Jogja',
            "Kode verifikasi email Anda adalah: {$code}\n\nKode berlaku selama "
                . self::CODE_EXPIRE_MINUTES
                . " menit. Abaikan email ini jika Anda tidak meminta kode."
        );

        $this->repository->storeCode(
            'email',
            $email,
            password_hash($code, PASSWORD_DEFAULT),
            $this->expiresAt(),
            $this->sentAt()
        );
    }

    public function verifyEmailCode(array $data): void
    {
        $email = $this->normalizeEmail($data['email'] ?? null);
        $code = $this->normalizeCode($data['code'] ?? null);
        $this->verifyCode('email', $email, $code);
    }

    public function sendPhoneOtp(array $data): void
    {
        $phone = $this->normalizePhone($data['phone'] ?? null);
        $this->ensureCanSend('phone', $phone);

        $code = $this->generateCode();
        $this->smsService->send(
            $phone,
            "Kode OTP Survey PemKot Jogja Anda: {$code}. Berlaku "
                . self::CODE_EXPIRE_MINUTES
                . " menit."
        );

        $this->repository->storeCode(
            'phone',
            $phone,
            password_hash($code, PASSWORD_DEFAULT),
            $this->expiresAt(),
            $this->sentAt()
        );
    }

    public function verifyPhoneOtp(array $data): void
    {
        $phone = $this->normalizePhone($data['phone'] ?? null);
        $code = $this->normalizeCode($data['code'] ?? null);
        $this->verifyCode('phone', $phone, $code);
    }

    private function normalizeEmail(mixed $email): string
    {
        if (!\is_string($email)) {
            throw new RuntimeException('Email harus berupa string', 422);
        }

        $email = strtolower(trim($email));

        if ($email === '') {
            throw new RuntimeException('Email harus diisi', 422);
        }

        if (mb_strlen($email) > 255) {
            throw new RuntimeException('Email maksimal 255 karakter', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Format email tidak valid', 422);
        }

        return $email;
    }

    private function normalizePhone(mixed $phone): string
    {
        if (!\is_string($phone)) {
            throw new RuntimeException('Nomor telepon harus berupa string', 422);
        }

        $phone = trim($phone);

        if ($phone === '') {
            throw new RuntimeException('Nomor telepon harus diisi', 422);
        }

        $phone = preg_replace('/[\s().-]/', '', $phone);

        if (!\is_string($phone) || !preg_match('/^\+?[0-9]+$/', $phone)) {
            throw new RuntimeException('Format nomor telepon tidak valid', 422);
        }

        if (str_starts_with($phone, '+62')) {
            $normalizedPhone = $phone;
        } elseif (str_starts_with($phone, '62')) {
            $normalizedPhone = '+' . $phone;
        } elseif (str_starts_with($phone, '0')) {
            $normalizedPhone = '+62' . substr($phone, 1);
        } else {
            throw new RuntimeException('Format nomor telepon harus diawali dengan +62, 62, atau 08', 422);
        }

        if (!preg_match('/^\+62[0-9]{8,13}$/', $normalizedPhone)) {
            throw new RuntimeException('Format nomor telepon tidak valid', 422);
        }

        return $normalizedPhone;
    }

    private function normalizeCode(mixed $code): string
    {
        if (!\is_string($code)) {
            throw new RuntimeException('Kode verifikasi harus berupa string', 422);
        }

        $code = trim($code);

        if (!preg_match('/^[0-9]{6}$/', $code)) {
            throw new RuntimeException('Kode verifikasi harus 6 digit angka', 422);
        }

        return $code;
    }

    private function ensureCanSend(string $channel, string $target): void
    {
        $latestCode = $this->repository->latestCode($channel, $target);

        if (!$latestCode || empty($latestCode['sent_at'])) {
            return;
        }

        $sentAt = strtotime((string) $latestCode['sent_at']);

        if ($sentAt === false) {
            return;
        }

        $remainingSeconds = self::CODE_COOLDOWN_SECONDS - (time() - $sentAt);

        if ($remainingSeconds > 0) {
            throw new RuntimeException(
                "Tunggu {$remainingSeconds} detik sebelum mengirim kode lagi",
                429
            );
        }
    }

    private function verifyCode(string $channel, string $target, string $code): void
    {
        $latestCode = $this->repository->latestCode($channel, $target);

        if (!$latestCode) {
            throw new RuntimeException('Kode verifikasi belum dikirim', 404);
        }

        if (!empty($latestCode['verified_at'])) {
            return;
        }

        $expiresAt = strtotime((string) $latestCode['expires_at']);

        if ($expiresAt === false || $expiresAt < time()) {
            throw new RuntimeException('Kode verifikasi sudah kedaluwarsa', 422);
        }

        if ((int) $latestCode['attempts'] >= self::MAX_ATTEMPTS) {
            throw new RuntimeException('Percobaan kode verifikasi terlalu banyak', 429);
        }

        if (!password_verify($code, (string) $latestCode['code'])) {
            $this->repository->incrementAttempts((int) $latestCode['id']);
            throw new RuntimeException('Kode verifikasi tidak sesuai', 422);
        }

        $this->repository->markVerified((int) $latestCode['id']);
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function expiresAt(): string
    {
        return date('Y-m-d H:i:s', time() + (self::CODE_EXPIRE_MINUTES * 60));
    }

    private function sentAt(): string
    {
        return date('Y-m-d H:i:s');
    }
}
