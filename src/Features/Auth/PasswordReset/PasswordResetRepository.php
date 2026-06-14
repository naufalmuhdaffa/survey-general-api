<?php

declare(strict_types=1);

namespace App\Features\Auth\PasswordReset;

use App\Database;
use PDO;

final class PasswordResetRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getUserByEmail(string $email): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, email
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    public function latestTokenByUserId(int $userId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT sent_at
            FROM password_resets
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);

        return $stmt->fetch();
    }

    public function storeToken(
        int $userId,
        string $email,
        string $selector,
        string $tokenHash,
        string $expiresAt,
        string $sentAt
    ): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO password_resets (
                user_id,
                email,
                selector,
                token_hash,
                expires_at,
                sent_at
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $email,
            $selector,
            $tokenHash,
            $expiresAt,
            $sentAt,
        ]);
    }

    public function getTokenBySelector(string $selector): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, user_id, token_hash, expires_at, used_at
            FROM password_resets
            WHERE selector = ?
            LIMIT 1
        ");
        $stmt->execute([$selector]);

        return $stmt->fetch();
    }

    public function resetPassword(int $resetId, int $userId, string $password): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");
            $stmt->execute([
                password_hash($password, PASSWORD_DEFAULT),
                $userId,
            ]);

            $stmt = $this->pdo->prepare("
                UPDATE password_resets
                SET used_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$resetId]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
