<?php

declare(strict_types=1);

namespace App\Features\User\Profile;

use App\Database;
use PDO;

final class ProfileRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getById(int $userId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT
                u.id,
                u.nik,
                u.full_name,
                u.username,
                u.email,
                u.phone,
                u.email_verified_at,
                u.phone_verified_at,
                u.password,
                u.role_id,
                r.name AS role,
                u.position,
                u.is_active,
                u.created_at,
                u.updated_at
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);

        return $stmt->fetch();
    }

    public function emailExistsForOtherUser(string $email, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ? AND id <> ?
            LIMIT 1
        ");
        $stmt->execute([$email, $userId]);

        return (bool) $stmt->fetch();
    }

    public function phoneExistsForOtherUser(string $phone, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM users
            WHERE phone = ? AND id <> ?
            LIMIT 1
        ");
        $stmt->execute([$phone, $userId]);

        return (bool) $stmt->fetch();
    }

    /**
     * @param array{email?: ?string, phone?: ?string} $fields
     */
    public function updateProfile(
        int $userId,
        array $fields,
        bool $resetEmailVerification,
        bool $resetPhoneVerification
    ): void {
        $set = [];
        $values = [];

        foreach (['email', 'phone'] as $field) {
            if (!array_key_exists($field, $fields)) {
                continue;
            }

            $set[] = "{$field} = ?";
            $values[] = $fields[$field];
        }

        if ($resetEmailVerification) {
            $set[] = 'email_verified_at = NULL';
        }

        if ($resetPhoneVerification) {
            $set[] = 'phone_verified_at = NULL';
        }

        if ($set === []) {
            return;
        }

        $values[] = $userId;

        $stmt = $this->pdo->prepare("
            UPDATE users
            SET " . implode(', ', $set) . "
            WHERE id = ?
        ");
        $stmt->execute($values);
    }

    public function markEmailVerified(int $userId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET email_verified_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }

    public function markPhoneVerified(int $userId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET phone_verified_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }

    public function updatePassword(int $userId, string $password): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");
        $stmt->execute([
            password_hash($password, PASSWORD_DEFAULT),
            $userId,
        ]);
    }
}
