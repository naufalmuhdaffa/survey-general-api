<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class SmsService
{
    public function send(string $phone, string $message): void
    {
        $target = $this->targetPhone($phone);
        $providerUrl = trim($_ENV['SMS_PROVIDER_URL'] ?? '');

        if ($providerUrl === '') {
            if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                $this->writeLog($target, $message);
                return;
            }

            throw new RuntimeException('SMS provider belum dikonfigurasi', 500);
        }

        $this->sendToProvider($providerUrl, $target, $message);
    }

    private function targetPhone(string $phone): string
    {
        $redirectPhone = trim($_ENV['SMS_DEV_REDIRECT_PHONE'] ?? '');

        if (($_ENV['APP_ENV'] ?? 'production') === 'development' && $redirectPhone !== '') {
            return $redirectPhone;
        }

        return $phone;
    }

    private function sendToProvider(string $providerUrl, string $phone, string $message): void
    {
        $payload = json_encode([
            'to' => $phone,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!\is_string($payload)) {
            throw new RuntimeException('Payload SMS tidak valid', 500);
        }

        $headers = ['Content-Type: application/json'];
        $token = trim($_ENV['SMS_PROVIDER_TOKEN'] ?? '');

        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($providerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Gagal mengirim OTP nomor telepon', 502);
        }
    }

    private function writeLog(string $phone, string $message): void
    {
        $logDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';

        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0775, true);
        }

        file_put_contents(
            $logDirectory . DIRECTORY_SEPARATOR . 'sms.log',
            sprintf("[%s] To: %s | %s%s", date('c'), $phone, $message, PHP_EOL),
            FILE_APPEND
        );
    }
}
