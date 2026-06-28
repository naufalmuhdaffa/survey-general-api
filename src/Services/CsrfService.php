<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Response;
use stdClass;

final class CsrfService
{
    private const string HEADER_NAME = 'HTTP_X_CSRF_TOKEN';
    private const int EXPIRED_COOKIE = 3600;

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function setToken(string $token): void
    {
        setcookie(self::name(), $token, self::options(time() + self::ttl()));
    }

    public static function clearToken(): void
    {
        setcookie(self::name(), '', self::options(time() - max(self::ttl(), self::EXPIRED_COOKIE)));
    }

    public static function issueFromPayload(stdClass $payload): void
    {
        $token = self::payloadToken($payload);

        if ($token !== null) {
            self::setToken($token);
        }
    }

    public static function enforceForUnsafeRequest(stdClass $payload): void
    {
        if (!self::isUnsafeMethod()) {
            self::issueFromPayload($payload);
            return;
        }

        $expectedToken = self::payloadToken($payload);
        $headerToken = self::headerToken();
        $cookieToken = self::cookieToken();

        if (
            $expectedToken === null ||
            $headerToken === null ||
            $cookieToken === null ||
            !hash_equals($expectedToken, $headerToken) ||
            !hash_equals($expectedToken, $cookieToken)
        ) {
            Response::json([
                'status' => 'error',
                'message' => 'Token CSRF tidak valid',
            ], 419);
        }

        self::issueFromPayload($payload);
    }

    private static function payloadToken(stdClass $payload): ?string
    {
        $token = $payload->data->csrfToken ?? null;

        if (!\is_string($token) || trim($token) === '') {
            return null;
        }

        return $token;
    }

    private static function headerToken(): ?string
    {
        $token = $_SERVER[self::HEADER_NAME] ?? null;

        if (!\is_string($token) || trim($token) === '') {
            return null;
        }

        return $token;
    }

    private static function cookieToken(): ?string
    {
        $token = $_COOKIE[self::name()] ?? null;

        if (!\is_string($token) || trim($token) === '') {
            return null;
        }

        return $token;
    }

    private static function isUnsafeMethod(): bool
    {
        return \in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private static function name(): string
    {
        return $_ENV['CSRF_COOKIE_NAME'] ?? 'csrf_token';
    }

    private static function options(int $expires): array
    {
        $options = [
            'expires' => $expires,
            'path' => $_ENV['COOKIE_PATH'] ?? '/',
            'secure' => self::secure(),
            'httponly' => false,
            'samesite' => self::sameSite(),
        ];

        $domain = trim($_ENV['COOKIE_DOMAIN'] ?? '');

        if ($domain !== '') {
            $options['domain'] = $domain;
        }

        return $options;
    }

    private static function ttl(): int
    {
        return (int) ($_ENV['JWT_EXPIRE'] ?? 3600);
    }

    private static function secure(): bool
    {
        $configured = $_ENV['COOKIE_SECURE'] ?? null;

        if ($configured !== null) {
            return \in_array(strtolower((string) $configured), ['1', 'true', 'yes'], true);
        }

        return ($_ENV['APP_ENV'] ?? 'production') !== 'development';
    }

    private static function sameSite(): string
    {
        $sameSite = ucfirst(strtolower($_ENV['COOKIE_SAMESITE'] ?? 'Lax'));

        if (!\in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
            return 'Lax';
        }

        return $sameSite;
    }
}
