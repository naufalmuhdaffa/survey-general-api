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

    public function registerUser(
        string $nik,
        string $fullName,
        string $username,
        string $password,
        string $position
    ): int {
        try {
            $stmt = $this->pdo->prepare("
            INSERT INTO users (nik, full_name, username, password, position)
            VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $nik,
                $fullName,
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $position
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Registrasi gagal');
        }
    }
}