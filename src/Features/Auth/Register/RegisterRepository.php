<?php

declare(strict_types=1);

namespace App\Features\Auth\Register;

use PDO;
use App\Database;

final class RegisterRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getUserByNik(
        string $nik
    ): array|false {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE nik = ?");
        $stmt->execute([$nik]);
        return $stmt->fetch();
    }

    public function getUserByUsername(
        string $username
    ): array|false {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function getUserByEmail(
        string $email
    ): array|false {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getUserByPhone(
        string $phone
    ): array|false {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetch();
    }

    public function registerUser(
        string $nik,
        string $fullName,
        string $username,
        ?string $email,
        ?string $phone,
        ?string $emailVerifiedAt,
        ?string $phoneVerifiedAt,
        string $password,
        string $position,
        ?string $opdPengampu
    ): int {
        try {
            $stmt = $this->pdo->prepare("
            INSERT INTO users (
                nik,
                full_name,
                username,
                email,
                phone,
                email_verified_at,
                phone_verified_at,
                password,
                position,
                opd_pengampu
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $nik,
                $fullName,
                $username,
                $email,
                $phone,
                $emailVerifiedAt,
                $phoneVerifiedAt,
                password_hash($password, PASSWORD_DEFAULT),
                $position,
                $opdPengampu,
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Registrasi gagal');
        }
    }
}
