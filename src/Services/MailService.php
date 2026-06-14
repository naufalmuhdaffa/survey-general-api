<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class MailService
{
    public function send(string $to, string $subject, string $body): void
    {
        $transport = strtolower(trim($_ENV['MAIL_TRANSPORT'] ?? ''));

        if ($transport === '') {
            $transport = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'log' : 'mail';
        }

        match ($transport) {
            'log' => $this->writeLog($to, $subject, $body),
            'smtp' => $this->sendSmtp($to, $subject, $body),
            'mail' => $this->sendWithMail($to, $subject, $body),
            default => throw new RuntimeException('MAIL_TRANSPORT tidak valid', 500),
        };
    }

    private function sendWithMail(string $to, string $subject, string $body): void
    {
        $fromEmail = $this->fromEmail();
        $fromName = $this->fromName();

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
        ];

        if (!mail($to, $subject, $body, implode("\r\n", $headers))) {
            throw new RuntimeException('Gagal mengirim email verifikasi', 502);
        }
    }

    private function sendSmtp(string $to, string $subject, string $body): void
    {
        $host = trim($_ENV['MAIL_SMTP_HOST'] ?? '');

        if ($host === '') {
            throw new RuntimeException('MAIL_SMTP_HOST belum dikonfigurasi', 500);
        }

        $port = (int) ($_ENV['MAIL_SMTP_PORT'] ?? 587);
        $encryption = strtolower(trim($_ENV['MAIL_SMTP_ENCRYPTION'] ?? 'tls'));
        $remote = $encryption === 'ssl' ? "ssl://{$host}:{$port}" : "{$host}:{$port}";
        $socket = stream_socket_client($remote, $errno, $error, 15, STREAM_CLIENT_CONNECT);

        if (!\is_resource($socket)) {
            throw new RuntimeException('Gagal terhubung ke SMTP: ' . $error, 502);
        }

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Gagal mengaktifkan TLS SMTP', 502);
                }

                $this->command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);
            }

            $username = $_ENV['MAIL_SMTP_USERNAME'] ?? '';
            $password = $_ENV['MAIL_SMTP_PASSWORD'] ?? '';

            if ($username !== '') {
                $this->command($socket, 'AUTH LOGIN', [334]);
                $this->command($socket, base64_encode($username), [334]);
                $this->command($socket, base64_encode($password), [235]);
            }

            $fromEmail = $this->fromEmail();
            $fromName = $this->fromName();
            $headers = implode("\r\n", [
                'From: ' . $fromName . ' <' . $fromEmail . '>',
                'To: <' . $to . '>',
                'Subject: ' . $subject,
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
            ]);
            $payload = $headers . "\r\n\r\n" . $this->dotStuff($body) . "\r\n.";

            $this->command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);
            $this->command($socket, $payload, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    /**
     * @param resource $socket
     * @param array<int> $expectedCodes
     */
    private function command($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $expectedCodes);
    }

    /**
     * @param resource $socket
     * @param array<int> $expectedCodes
     */
    private function expect($socket, array $expectedCodes): string
    {
        $response = '';

        while (($line = fgets($socket, 512)) !== false) {
            $response .= $line;

            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);

        if (!\in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP menolak permintaan: ' . trim($response), 502);
        }

        return $response;
    }

    private function dotStuff(string $body): string
    {
        return preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $body)) ?? $body;
    }

    private function writeLog(string $to, string $subject, string $body): void
    {
        $logDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';

        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0775, true);
        }

        file_put_contents(
            $logDirectory . DIRECTORY_SEPARATOR . 'mail.log',
            sprintf(
                "[%s] To: %s | Subject: %s%s%s%s",
                date('c'),
                $to,
                $subject,
                PHP_EOL,
                $body,
                PHP_EOL . PHP_EOL
            ),
            FILE_APPEND
        );
    }

    private function fromEmail(): string
    {
        return trim($_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@survey-jogja.test');
    }

    private function fromName(): string
    {
        return trim($_ENV['MAIL_FROM_NAME'] ?? 'Survey PemKot Jogja');
    }
}
