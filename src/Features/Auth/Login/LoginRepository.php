<?php

declare(strict_types=1);

namespace App\Features\Auth\Login;

use PDO;
use App\Database;

final class LoginRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getUserByNik(string $nik): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username, password, role, position, is_active
            FROM users
            WHERE nik = ?
        ");
        $stmt->execute([$nik]);
        return $stmt->fetch();
    }

    public function getUserByUsername(string $username): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username, password, role, position, is_active
            FROM users
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
}