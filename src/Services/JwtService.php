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
        $exp = $now + (int)($_ENV['JWT_EXPIRE'] ?? 3600);

        return JWT::encode([
            'iat' => $now,
            'exp' => $exp,
            'data' => $payload,
        ], self::secret(), 'HS256');
    }

    private static function secret(): string
    {
        return $_ENV['JWT_SECRET'] ?? 'secret-key';
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

    public static function bearerToken(): ?string
    {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            return null;
        }

        if (preg_match('/Bearer\s+(\S+)/', $headers['Authorization'], $m)) {
            return $m[1];
        }

        return null;
    }
}
