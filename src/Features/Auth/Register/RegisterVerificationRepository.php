<?php

declare(strict_types=1);

namespace App\Features\Auth\Register;

use App\Database;
use PDO;

final class RegisterVerificationRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function latestCode(string $channel, string $target): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, code, attempts, expires_at, verified_at, sent_at
            FROM contact_verifications
            WHERE channel = ? AND target = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$channel, $target]);
        return $stmt->fetch();
    }

    public function isVerified(string $channel, string $target): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM contact_verifications
            WHERE channel = ? AND target = ? AND verified_at IS NOT NULL
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$channel, $target]);

        return (bool) $stmt->fetch();
    }

    public function storeCode(
        string $channel,
        string $target,
        string $codeHash,
        string $expiresAt,
        string $sentAt
    ): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO contact_verifications (channel, target, code, expires_at, sent_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$channel, $target, $codeHash, $expiresAt, $sentAt]);
    }

    public function incrementAttempts(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE contact_verifications
            SET attempts = attempts + 1
            WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    public function markVerified(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE contact_verifications
            SET verified_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$id]);
    }
}
