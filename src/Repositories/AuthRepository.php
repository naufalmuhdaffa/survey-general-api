<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

final class AuthRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function findById(int $userId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT
                u.id,
                u.username,
                u.role_id,
                r.name AS role,
                default_role.id AS default_role_id,
                default_role.name AS default_role,
                u.position,
                u.is_active
            FROM users u
            JOIN roles r ON r.id = u.role_id
            JOIN roles default_role ON default_role.name = 'user'
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function isTokenRevoked(string $token): bool
    {
        $this->deleteExpiredRevokedTokens();

        $stmt = $this->pdo->prepare("
            SELECT id
            FROM revoked_tokens
            WHERE token_hash = ? AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([hash('sha256', $token)]);

        return (bool) $stmt->fetch();
    }

    public function revokeToken(string $token, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO revoked_tokens (token_hash, expires_at)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE
                expires_at = VALUES(expires_at),
                revoked_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            hash('sha256', $token),
            $expiresAt,
        ]);
    }

    private function deleteExpiredRevokedTokens(): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM revoked_tokens
            WHERE expires_at <= NOW()
        ");
        $stmt->execute();
    }
}
