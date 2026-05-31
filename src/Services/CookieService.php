<?php

declare(strict_types=1);

namespace App\Services;

final class CookieService
{
    private const int EXPIRED_COOKIE = 3600;

    public static function setToken(string $token): void
    {
        setcookie(self::name(), $token, self::options(time() + self::ttl()));
    }

    public static function clearToken(): void
    {
        setcookie(self::name(), '', self::options(time() - max(self::ttl(), self::EXPIRED_COOKIE)));
    }

    public static function token(): ?string
    {
        $token = $_COOKIE[self::name()] ?? null;

        if (!\is_string($token) || trim($token) === '') {
            return null;
        }

        return $token;
    }

    public static function name(): string
    {
        return $_ENV['COOKIE_NAME'] ?? 'access_token';
    }

    private static function options(int $expires): array
    {
        $options = [
            'expires' => $expires,
            'path' => $_ENV['COOKIE_PATH'] ?? '/',
            'secure' => self::secure(),
            'httponly' => true,
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
