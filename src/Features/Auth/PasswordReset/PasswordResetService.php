<?php

declare(strict_types=1);

namespace App\Features\Auth\PasswordReset;

use App\Services\MailService;
use RuntimeException;

final class PasswordResetService
{
    private const int TOKEN_COOLDOWN_SECONDS = 60;
    private const int TOKEN_EXPIRE_MINUTES = 30;

    private PasswordResetRepository $repository;
    private MailService $mailService;

    public function __construct()
    {
        $this->repository = new PasswordResetRepository();
        $this->mailService = new MailService();
    }

    public function requestReset(array $data): void
    {
        $email = $this->normalizeEmail($data['email'] ?? null);
        $user = $this->repository->getUserByEmail($email);

        if (!$user) {
            return;
        }

        $userId = (int) $user['id'];
        $this->ensureCanSend($userId);

        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $token = $selector . '.' . $validator;
        $resetUrl = $this->resetUrl($token);

        $this->repository->storeToken(
            $userId,
            $email,
            $selector,
            password_hash($validator, PASSWORD_DEFAULT),
            $this->expiresAt(),
            $this->sentAt()
        );

        $this->mailService->sendHtml(
            $email,
            'Atur Ulang Kata Sandi Survey PemKot Jogja',
            $this->htmlEmail($resetUrl),
            $this->textEmail($resetUrl)
        );
    }

    public function resetPassword(array $data): void
    {
        $token = $this->normalizeToken($data['token'] ?? null);
        $password = isset($data['password']) && \is_string($data['password'])
            ? $data['password']
            : '';
        $passwordConfirmation = isset($data['passwordConfirmation']) && \is_string($data['passwordConfirmation'])
            ? $data['passwordConfirmation']
            : '';

        if (trim($password) === '') {
            throw new RuntimeException('Kata sandi baru harus diisi', 422);
        }

        if (mb_strlen($password) < 6) {
            throw new RuntimeException('Kata sandi baru minimal 6 karakter', 422);
        }

        if (mb_strlen($password) > 255) {
            throw new RuntimeException('Kata sandi baru maksimal 255 karakter', 422);
        }

        if ($password !== $passwordConfirmation) {
            throw new RuntimeException('Konfirmasi kata sandi baru belum sama', 422);
        }

        [$selector, $validator] = explode('.', $token, 2);
        $resetToken = $this->repository->getTokenBySelector($selector);

        if (!$resetToken) {
            throw new RuntimeException('Link reset kata sandi tidak valid atau sudah kedaluwarsa', 422);
        }

        if (!empty($resetToken['used_at'])) {
            throw new RuntimeException('Link reset kata sandi sudah digunakan', 422);
        }

        $expiresAt = strtotime((string) $resetToken['expires_at']);

        if ($expiresAt === false || $expiresAt < time()) {
            throw new RuntimeException('Link reset kata sandi sudah kedaluwarsa', 422);
        }

        if (!password_verify($validator, (string) $resetToken['token_hash'])) {
            throw new RuntimeException('Link reset kata sandi tidak valid atau sudah kedaluwarsa', 422);
        }

        $this->repository->resetPassword(
            (int) $resetToken['id'],
            (int) $resetToken['user_id'],
            $password
        );
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

    private function normalizeToken(mixed $token): string
    {
        if (!\is_string($token)) {
            throw new RuntimeException('Token reset kata sandi tidak valid', 422);
        }

        $token = trim($token);

        if (!preg_match('/^[a-f0-9]{32}\.[a-f0-9]{64}$/', $token)) {
            throw new RuntimeException('Token reset kata sandi tidak valid', 422);
        }

        return $token;
    }

    private function ensureCanSend(int $userId): void
    {
        $latestToken = $this->repository->latestTokenByUserId($userId);

        if (!$latestToken || empty($latestToken['sent_at'])) {
            return;
        }

        $sentAt = strtotime((string) $latestToken['sent_at']);

        if ($sentAt === false) {
            return;
        }

        $remainingSeconds = self::TOKEN_COOLDOWN_SECONDS - (time() - $sentAt);

        if ($remainingSeconds > 0) {
            throw new RuntimeException(
                "Tunggu {$remainingSeconds} detik sebelum mengirim instruksi lagi",
                429
            );
        }
    }

    private function resetUrl(string $token): string
    {
        $frontendUrl = rtrim(
            trim($_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173'),
            '/'
        );
        $separator = str_contains($frontendUrl, '?') ? '&' : '?';

        return $frontendUrl . $separator . http_build_query([
            'reset_token' => $token,
        ]);
    }

    private function htmlEmail(string $resetUrl): string
    {
        $safeResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Atur Ulang Kata Sandi</title>
</head>
<body style="margin:0;background:#f8f9ff;font-family:Inter,Segoe UI,Arial,sans-serif;color:#300000;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9ff;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:440px;background:#ffffff;border:1px solid rgba(222,192,187,0.4);border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
          <tr>
            <td style="padding:40px 32px;text-align:center;">
              <div style="display:inline-block;width:56px;height:56px;border-radius:999px;background:#ffdad4;line-height:56px;font-size:24px;font-weight:700;color:#800000;">RS</div>
              <h1 style="margin:18px 0 8px;font-size:24px;line-height:32px;color:#300000;">Atur Ulang Kata Sandi</h1>
              <p style="margin:0 0 24px;font-size:14px;line-height:22px;color:#5a413d;">Klik tombol di bawah ini untuk membuat kata sandi baru akun Survey Jogja Anda. Link berlaku selama 30 menit.</p>
              <a href="{$safeResetUrl}" style="display:inline-block;background:#570000;color:#ffffff;text-decoration:none;border-radius:999px;padding:14px 28px;font-size:14px;font-weight:700;">Atur Ulang Kata Sandi</a>
              <p style="margin:24px 0 0;font-size:12px;line-height:18px;color:#8b716d;">Jika Anda tidak meminta reset kata sandi, abaikan email ini.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    private function textEmail(string $resetUrl): string
    {
        return "Klik link berikut untuk mengatur ulang kata sandi Survey Jogja Anda:\n"
            . $resetUrl
            . "\n\nLink berlaku selama "
            . self::TOKEN_EXPIRE_MINUTES
            . " menit. Abaikan email ini jika Anda tidak meminta reset kata sandi.";
    }

    private function expiresAt(): string
    {
        return date('Y-m-d H:i:s', time() + (self::TOKEN_EXPIRE_MINUTES * 60));
    }

    private function sentAt(): string
    {
        return date('Y-m-d H:i:s');
    }
}
