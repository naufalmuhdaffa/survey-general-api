<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;
use Throwable;

final class JwtService
{
    public static function generate(array $payload): string
    {
        $now = time();
        $exp = $now + (int) ($_ENV['JWT_EXPIRE'] ?? 3600);

        return JWT::encode([
            'iat' => $now,
            'exp' => $exp,
            'data' => $payload,
        ], self::secret(), 'HS256');
    }

    private static function secret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? '';
        
        if (!\is_string($secret) || \strlen($secret) < 32) {
            throw new \RuntimeException('JWT_SECRET belum dikonfigurasi dengan aman');
        }

        return $secret;
    }

    public static function verify(string $token): ?stdClass
    {
        try {
            return JWT::decode($token, new Key(self::secret(), 'HS256'));
        } catch (Throwable $e) {
            error_log('JWT error: ' . $e->getMessage());
            return null;
        }
    }

    public static function token(): ?string
    {
        return CookieService::token();
    }
}
